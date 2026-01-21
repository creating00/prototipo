<?php

namespace App\Services\Product;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductBranch;
use Illuminate\Validation\ValidationException;

class ProductStockService
{
    /**
     * Reservar stock de un producto en una sucursal específica
     */
    public function reserve(Product $product, int $qty, int $branchId): void
    {
        $productBranch = $product->productBranches()
            ->where('branch_id', $branchId)
            ->lockForUpdate()
            ->first();

        if (!$productBranch) {
            throw ValidationException::withMessages([
                'stock' => "El producto {$product->name} no está disponible en la sucursal {$branchId}"
            ]);
        }

        if ($productBranch->stock < $qty) {
            throw ValidationException::withMessages([
                'stock' => "No hay suficiente stock. Disponible: {$productBranch->stock}"
            ]);
        }

        $productBranch->stock -= $qty;

        $this->syncStatus($productBranch);

        $productBranch->save();
    }

    /**
     * Liberar stock de un producto en una sucursal específica
     */
    public function release(Product $product, int $qty, int $branchId): void
    {
        $productBranch = $product->productBranches()
            ->where('branch_id', $branchId)
            ->lockForUpdate()
            ->first();

        if (!$productBranch) {
            return;
        }

        $productBranch->stock += $qty;

        $this->syncStatus($productBranch);

        $productBranch->save();
    }

    /**
     * Aumenta el stock en una sucursal. 
     * Si el producto no existe en esa sucursal, lo crea.
     */
    public function addStock(Product $product, int $quantity, int $branchId): void
    {
        $productBranch = $product->productBranches()->updateOrCreate(
            ['branch_id' => $branchId],
            [
                'status' => ProductStatus::Available,
            ]
        );

        $productBranch->increment('stock', $quantity);

        // Seguridad extra por si estaba en OutOfStock
        if ($productBranch->stock > 0 && $productBranch->status !== ProductStatus::Available) {
            $productBranch->status = ProductStatus::Available;
            $productBranch->save();
        }
    }

    private function syncStatus(ProductBranch $branch): void
    {
        if ($branch->stock <= 0) {
            $branch->status = ProductStatus::OutOfStock;
        } elseif ($branch->stock <= $branch->low_stock_threshold) {
            $branch->status = ProductStatus::LowStock;
        } else {
            $branch->status = ProductStatus::Available;
        }
    }

    /**
     * Actualiza el precio de compra para un producto en una sucursal específica.
     */
    public function updatePurchasePrice(Product $product, int $branchId, float $amount, int $currency): void
    {
        // 1. Obtenemos el ProductBranch (el pivote entre producto y sucursal)
        $productBranch = $product->productBranches()->where('branch_id', $branchId)->first();

        if (!$productBranch) {
            return; // Opcional: podrías lanzas una excepción si prefieres
        }

        // 2. Actualizamos o creamos el precio de tipo PURCHASE
        $productBranch->prices()->updateOrCreate(
            ['type' => \App\Enums\PriceType::PURCHASE],
            [
                'amount'   => $amount,
                'currency' => $currency
            ]
        );
    }
}
