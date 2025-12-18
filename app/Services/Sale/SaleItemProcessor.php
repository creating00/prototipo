<?php

namespace App\Services\Sale;

use App\Services\BaseItemProcessor;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class SaleItemProcessor extends BaseItemProcessor
{
    /**
     * @param Sale $model
     */
    protected function createItem(
        Model $model,
        Product $product,
        int $quantity,
        float $unitPrice,
        float $subtotal
    ): void {
        $model->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
        ]);
    }

    protected function validateStock(Product $product, int $branchId, int $quantity): void
    {
        $branchStock = $product->getStock($branchId);

        if ($branchStock < $quantity) {
            throw new \Exception("No hay suficiente stock para el producto {$product->name}. Disponible: {$branchStock}");
        }
    }

    protected function getProductPrice(Product $product, int $branchId): float
    {
        $unitPrice = $product->salePrice($branchId);

        if (!$unitPrice) {
            throw new \Exception("No se encontró un precio de venta para {$product->name} en la sucursal {$branchId}");
        }

        return $unitPrice;
    }
}
