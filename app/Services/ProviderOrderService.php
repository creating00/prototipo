<?php

namespace App\Services;

use App\Models\{
    Provider,
    ProviderOrder,
    ProviderOrderItem,
    ProviderProduct
};
use App\Enums\ProviderOrderStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProviderOrderService
{
    /**
     * Crear una orden de compra en estado DRAFT
     */
    public function createOrder(Provider $provider): ProviderOrder
    {
        return ProviderOrder::create([
            'provider_id' => $provider->id,
            'order_date'  => now(),
            'status'      => ProviderOrderStatus::DRAFT->value,
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
        // Si no viene precio, tomamos el vigente
        $price = $providerProduct->currentPrice;

        if ($unitCost === null && !$price) {
            throw new \RuntimeException('El producto no tiene precio vigente.');
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
