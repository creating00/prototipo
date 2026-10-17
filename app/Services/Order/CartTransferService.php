<?php

namespace App\Services\Order;

use App\Enums\CurrencyType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Traits\AuthTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CartTransferService
{
    use AuthTrait;

    public function __construct(
        private OrderItemProcessor $items,
        private OrderTransferService $transfers,
    ) {}

    public function send(array $data): Order
    {
        Gate::authorize('createClient', Sale::class);
        Gate::authorize('createBranch', Order::class);

        $origin = Branch::find($this->currentBranchId());
        if ($this->isConsolidatedMode() || ! $origin
            || (int) $origin->province_id !== (int) $this->currentProvinceId()
            || (int) $data['branch_id'] !== (int) $origin->id) {
            throw ValidationException::withMessages(['branch_id' => 'El origen debe ser la sucursal activa de su provincia.']);
        }

        $destination = Branch::where('province_id', $origin->province_id)
            ->where('id', '!=', $origin->id)->find($data['destination_branch_id']);
        if (! $destination) {
            throw ValidationException::withMessages(['destination_branch_id' => 'Seleccione otra sucursal de la misma provincia.']);
        }

        return DB::transaction(function () use ($data, $origin, $destination) {
            // Serialize retries from this user; the unique key also protects persisted requests.
            User::whereKey($this->userId())->lockForUpdate()->firstOrFail();
            $key = $this->userId().':'.$data['transfer_token'];
            $existing = Order::withTrashed()->where('cart_transfer_key', $key)->first();
            if ($existing) {
                if ((int) $existing->branch_id !== (int) $origin->id) {
                    throw ValidationException::withMessages(['transfer_token' => 'Este envío ya se procesó desde otra sucursal.']);
                }
                $requested = collect($data['items'])->mapWithKeys(fn ($item) => [(int) $item['product_id'] => (int) $item['quantity']])->sortKeys()->all();
                $sent = $existing->items()->where('destination_branch_id', $destination->id)
                    ->pluck('quantity', 'product_id')->sortKeys()->all();
                if ($requested !== $sent) {
                    throw ValidationException::withMessages(['transfer_token' => 'Este carrito ya fue enviado. Recargue la página para iniciar otro envío.']);
                }

                return $existing;
            }

            $items = collect($data['items'])->sortBy('product_id')->map(function ($item) use ($origin, $destination) {
                $product = Product::whereKey($item['product_id'])->lockForUpdate()->firstOrFail();
                $cost = $product->purchasePriceModel($origin->id);

                return [
                    'product_id' => $product->id,
                    'quantity' => (int) $item['quantity'],
                    'destination_branch_id' => $destination->id,
                    'destination_province_id' => $destination->province_id,
                    'unit_price' => (float) ($cost?->amount ?? 0),
                    'currency' => $cost?->currency?->value ?? CurrencyType::ARS->value,
                ];
            })->values()->all();

            $this->transfers->validateDestinations([
                'branch_id' => $origin->id,
                'source' => OrderSource::Backoffice->value,
                'items' => $items,
            ]);

            $order = Order::create([
                'branch_id' => $origin->id,
                'user_id' => $this->userId(),
                'customer_type' => Branch::class,
                'customer_id' => null,
                'is_multi_destination' => true,
                'source' => OrderSource::Backoffice,
                'status' => OrderStatus::Pending,
                'payment_status' => 2,
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'totals' => [],
                'cart_transfer_key' => $key,
                'notes' => 'Envío entre sucursales desde el carrito de Crear Venta.',
            ]);
            Gate::authorize('sendToStock', $order);
            $order->update(['totals' => $this->items->syncDestinations($order, $items)]);
            $this->transfers->transfer($order, updatePurchasePrices: false);

            return $order->fresh();
        });
    }
}
