<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\Product;
use App\Services\BaseItemProcessor;
use Illuminate\Database\Eloquent\Model;

class OrderItemProcessor extends BaseItemProcessor
{
    /** Synchronize transfer lines by product and destination, without moving inventory. */
    public function syncDestinations(Order $order, array $items): array
    {
        $totals = [];
        $retainedIds = [];

        foreach ($items as $item) {
            $product = $this->getLockedProduct($item['product_id']);
            $quantity = (int) $item['quantity'];
            $unitPrice = (float) ($item['unit_price'] ?? $product->purchasePrice($order->branch_id) ?? 0);
            $subtotal = $quantity * $unitPrice;
            $currency = $item['currency'];
            $line = $order->items()->updateOrCreate([
                'product_id' => $product->id,
                'destination_branch_id' => $item['destination_branch_id'],
            ], [
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'currency' => $currency,
            ]);
            $retainedIds[] = $line->id;
            $totals[$currency] = ($totals[$currency] ?? 0) + $subtotal;
        }

        $order->items()->whereNotIn('id', $retainedIds)->delete();

        return $totals;
    }

    /**
     * @param  Order  $model
     */
    protected function createItem(
        Model $model,
        Product $product,
        int $quantity,
        float $unitPrice,
        float $subtotal,
        array $rawItem
    ): void {
        $model->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'currency' => $rawItem['currency'],
        ]);
    }

    protected function validateStock(Product $product, int $branchId, int $quantity): void
    {
        $branchStock = $product->getStock($branchId);

        if ($branchStock === 0) {
            throw new \Exception("Stock no encontrado para el producto {$product->name} en la sucursal {$branchId}");
        }

        if ($branchStock < $quantity) {
            throw new \Exception("No hay suficiente stock para el producto {$product->name}. Disponible: {$branchStock}");
        }
    }

    protected function getProductPrice(Product $product, Model $model): float
    {
        $unitPrice = $product->salePrice($model->branch_id);

        if (! $unitPrice) {
            throw new \Exception("No se encontró un precio de venta para {$product->name} en esta sucursal.");
        }

        return $unitPrice;
    }
}
