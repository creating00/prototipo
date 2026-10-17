<?php

namespace App\Services\Order;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use App\Services\Product\ProductStockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class OrderTransferService
{
    public function __construct(private ProductStockService $stockService) {}

    public function validateDestinations(array $data): void
    {
        $branches = Branch::whereIn('id', collect($data['items'])->pluck('destination_branch_id'))->get()->keyBy('id');
        $sourceProductIds = Product::whereIn('id', collect($data['items'])->pluck('product_id'))
            ->whereHas('productBranches', fn ($branches) => $branches->where('branch_id', $data['branch_id']))
            ->pluck('id')->all();
        $seen = [];

        foreach ($data['items'] as $index => $item) {
            if (! in_array((int) $item['product_id'], $sourceProductIds, true)) {
                throw ValidationException::withMessages(["items.$index.product_id" => 'El producto no está registrado en la sucursal de origen.']);
            }
            $branch = $branches->get($item['destination_branch_id']);
            if (! $branch || ((int) $data['source'] !== OrderSource::Manual->value && (int) $branch->id === (int) $data['branch_id'])) {
                throw ValidationException::withMessages(["items.$index.destination_branch_id" => 'El destino debe ser una sucursal vigente distinta del origen.']);
            }
            if ((int) $branch->province_id !== (int) $item['destination_province_id']) {
                throw ValidationException::withMessages(["items.$index.destination_branch_id" => 'La sucursal no pertenece a la provincia seleccionada.']);
            }
            $key = $item['product_id'].':'.$branch->id;
            if (isset($seen[$key])) {
                throw ValidationException::withMessages(["items.$index.product_id" => 'Combine las cantidades del mismo producto y destino en un solo renglón.']);
            }
            $seen[$key] = true;
        }
    }

    /** The caller holds the order lock and transaction throughout the transfer. */
    public function transfer(Order $order, bool $updatePurchasePrices = true): void
    {
        $origin = Branch::findOrFail($order->branch_id);
        $items = $order->items()->with('destinationBranch')->orderBy('product_id')->orderBy('destination_branch_id')->get();
        if ($items->isEmpty() || $items->contains(fn ($item) => $item->stock_sent_at !== null)) {
            throw ValidationException::withMessages(['stock' => 'El pedido no tiene renglones pendientes válidos.']);
        }
        foreach ($items as $item) {
            if (! $item->destinationBranch || ($order->source !== OrderSource::Manual && $item->destination_branch_id === $origin->id)) {
                throw ValidationException::withMessages(['stock' => 'Hay una sucursal de destino inválida.']);
            }
        }

        // Product locks use a stable order; each source deduction covers all destinations.
        $products = [];
        foreach ($items->groupBy('product_id') as $productId => $lines) {
            $product = Product::whereKey($productId)->lockForUpdate()->firstOrFail();
            if ($order->source !== OrderSource::Manual) {
                $this->stockService->reserve($product, (int) $lines->sum('quantity'), $origin->id);
            }
            $products[$productId] = $product;
        }

        $sentAt = now();
        foreach ($items as $item) {
            $product = $products[$item->product_id];
            $this->stockService->addStock($product, $item->quantity, $item->destination_branch_id);
            if ($updatePurchasePrices) {
                $this->stockService->updatePurchasePrice($product, $item->destination_branch_id, (float) $item->unit_price, $item->currency->value);
            }
            $item->update(['stock_sent_at' => $sentAt]);
        }
        $order->update(['is_stock_sent' => true, 'stock_sent_at' => $sentAt, 'status' => OrderStatus::Confirmed]);
    }

    public function notifyParticipants(Order $order): void
    {
        DB::afterCommit(function () use ($order) {
            $order->load('items.destinationBranch');
            $groups = $order->items->groupBy('destination_branch_id');
            $groups->put($order->branch_id, $order->items);
            foreach ($groups as $branchId => $items) {
                $quantity = $items->sum('quantity');
                $direction = (int) $branchId === (int) $order->branch_id ? 'para enviar' : 'para recibir';
                $message = "Pedido {$order->order_number}: {$quantity} unidades {$direction}. Estado: {$order->status->label()}.";
                if ($order->status === OrderStatus::Cancelled && $order->cancellation_reason) {
                    $message .= " Motivo: {$order->cancellation_reason}";
                }
                Notification::send(User::where('branch_id', $branchId)->get(), new OrderStatusNotification($order, $message));
            }
        });
    }
}
