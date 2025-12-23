<?php

namespace App\Services;

use App\Models\{
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
        ?float $unitCost = null,
        ?int $currency = null
    ): ProviderOrderItem {

        // El Global Scope asegura que solo tomamos precios de la sucursal actual
        $price = $providerProduct->currentPrice;

        if ($unitCost === null && !$price) {
            throw new \RuntimeException('El producto no tiene precio vigente en esta sucursal.');
        }

        return $order->items()->create([
            'provider_product_id' => $providerProduct->id,
            'quantity'            => $quantity,
            'unit_cost'           => $unitCost ?? $price->cost_price,
            'currency'            => $currency ?? $price->currency->value,
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
        return DB::transaction(function () use ($order) {

            // Aquí luego impactará stock

            $order->update([
                'status' => ProviderOrderStatus::RECEIVED->value,
                'received_date' => now(),
            ]);

            return $order;
        });
    }
}
