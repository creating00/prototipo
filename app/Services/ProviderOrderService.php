<?php

namespace App\Services;

use App\Enums\PriceType;
use App\Enums\ProductStatus;
use App\Models\{
    ProductBranch,
    ProductBranchPrice,
    Provider,
    ProviderOrder,
    ProviderOrderItem,
    ProviderProduct
};
use App\Enums\ProviderOrderStatus;
use App\Services\Traits\DataTableFormatter;
use App\Traits\AuthTrait;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProviderOrderService
{
    use AuthTrait;
    use DataTableFormatter;

    /**
     * Crear una orden de compra en estado DRAFT
     */
    public function createOrder(Provider $provider): ProviderOrder
    {
        return ProviderOrder::create([
            'provider_id' => $provider->id,
            'order_date'  => now(),
            'status'      => ProviderOrderStatus::DRAFT->value,
            // branch_id se hereda del Auth::user() mediante el Trait
        ]);
    }

    /**
     * Agregar un producto a la orden
     */
    public function addItem(
        ProviderOrder $order,
        ProviderProduct $providerProduct,
        int $quantity,
        $unitCost = null,
        $currency = null
    ): ProviderOrderItem {

        // Intentamos obtener el precio actual de la DB como backup
        $currentPrice = $providerProduct->currentPrice;

        // Lógica de decisión:
        // 1. Usamos lo que viene por parámetro (del formulario)
        // 2. Si es null, usamos lo que tiene el producto en la DB
        $finalCost = $unitCost ?? ($currentPrice ? $currentPrice->cost_price : null);
        $finalCurrency = $currency ?? ($currentPrice ? $currentPrice->currency->value : null);

        // Si después de intentar ambos, seguimos sin tener datos, error
        if ($finalCost === null || $finalCurrency === null) {
            throw new \RuntimeException(
                "El producto {$providerProduct->product->name} no tiene precio definido ni se recibió uno válido."
            );
        }

        return $order->items()->create([
            'provider_product_id' => $providerProduct->id,
            'quantity'            => $quantity,
            'unit_cost'           => $finalCost,
            'currency'            => $finalCurrency,
        ]);
    }

    /**
     * Enviar la orden (cierra edición)
     */
    public function sendOrder(ProviderOrder $order): ProviderOrder
    {
        if ($order->items()->count() === 0) {
            throw new \RuntimeException('La orden no tiene productos.');
        }

        // Calcular fecha estimada de entrega
        $maxLeadTime = $order->items()
            ->with('providerProduct')
            ->get()
            ->max(fn($item) => $item->providerProduct->lead_time_days ?? 0);

        $order->update([
            'status' => ProviderOrderStatus::SENT->value,
            'expected_delivery_date' => now()->addDays($maxLeadTime),
        ]);

        return $order->fresh();
    }

    /**
     * Marcar orden como recibida
     */
    public function receiveOrder(ProviderOrder $order): ProviderOrder
    {
        if ($order->status === ProviderOrderStatus::RECEIVED) {
            throw new \RuntimeException('Esta orden ya ha sido marcada como recibida.');
        }

        return DB::transaction(function () use ($order) {
            $order->load('items.providerProduct.product');

            foreach ($order->items as $item) {
                $product = $item->providerProduct->product;

                // 1. Obtener o crear el registro de sucursal
                $productBranch = ProductBranch::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'branch_id'  => $order->branch_id,
                    ],
                    [
                        'stock'               => 0,
                        'low_stock_threshold' => 5,
                        'status'              => ProductStatus::Available,
                    ]
                );

                // 2. Incrementar Stock
                $productBranch->increment('stock', $item->quantity);

                // 3. Actualizar Precio de Compra
                ProductBranchPrice::updateOrCreate(
                    [
                        'product_branch_id' => $productBranch->id,
                        'type'              => PriceType::PURCHASE,
                        'currency'          => $item->currency,
                    ],
                    [
                        'amount'            => $item->unit_cost,
                    ]
                );
            }

            $order->update([
                'status'        => ProviderOrderStatus::RECEIVED->value,
                'received_date' => now(),
            ]);

            return $order->fresh();
        });
    }

    public function getAllOrdersForDataTable()
    {
        return ProviderOrder::with(['provider', 'items'])
            ->latest()
            ->get()
            ->map(fn($order, $index) => $this->formatForDataTable($order, $index))
            ->toArray();
    }

    protected function formatForDataTable($model, int $index, array $options = []): array
    {
        $totalsByCurrency = $model->items->groupBy('currency')
            ->map(function ($items) {
                $sum = $items->sum(fn($i) => $i->quantity * $i->unit_cost);
                $currencyEnum = $items->first()->currency;

                // Mapeo de colores basado en tus clases custom
                $color = match ($currencyEnum->name) {
                    'ARS' => '#10b981', // Emerald
                    'USD' => '#0d6efd', // Ocean
                    default => '#6c757d' // Gray
                };

                $formattedAmount = number_format($sum, 2, ',', '.');

                // Retornamos el texto con peso de fuente y color específico
                return "<div style='color: {$color}; font-weight: 700; white-space: nowrap;'>
                        {$currencyEnum->symbol()} {$formattedAmount}
                    </div>";
            });

        $totalHtml = $totalsByCurrency->implode('');

        return [
            'id' => $model->id,
            'number' => $index + 1,
            'order_id_text' => "#ORD-" . str_pad($model->id, 5, '0', STR_PAD_LEFT),
            'provider' => $model->provider->business_name,
            'order_date' => $model->order_date->format('d/m/Y'),
            'expected_delivery_date' => $model->expected_delivery_date?->format('d/m/Y') ?? 'Pendiente',
            'status' => $this->resolveStatus($model, $options),
            'status_raw' => $model->status->value,

            // Renderizado de texto en color
            'total' => $totalHtml ?: '<span style="color: #6c757d;">$ 0,00</span>',
        ];
    }
}
