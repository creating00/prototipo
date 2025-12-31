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
use App\Traits\AuthTrait;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProviderOrderService
{
    use AuthTrait;
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
}
