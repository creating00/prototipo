<?php

namespace App\Services\Product;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

class ProductStockService
{
    /**
     * Reservar stock de un producto en una sucursal específica
     */
    public function reserve(Product $product, int $qty, int $branchId): void
    {
        $productBranch = $product->productBranches()->where('branch_id', $branchId)->lockForUpdate()->first();

        if (!$productBranch) {
            throw ValidationException::withMessages([
                'stock' => "El producto {$product->name} no está disponible en la sucursal {$branchId}"
            ]);
        }

        if ($productBranch->stock < $qty) {
            throw ValidationException::withMessages([
                'stock' => "No hay suficiente stock en la sucursal seleccionada. Disponible: {$productBranch->stock}"
            ]);
        }

        $productBranch->stock -= $qty;
        $productBranch->save();
    }

    /**
     * Liberar stock de un producto en una sucursal específica
     */
    public function release(Product $product, int $qty, int $branchId): void
    {
        $productBranch = $product->productBranches()->where('branch_id', $branchId)->lockForUpdate()->first();

        if ($productBranch) {
            $productBranch->stock += $qty;
            $productBranch->save();
        }
    }

    /**
     * Aumenta el stock en una sucursal. 
     * Si el producto no existe en esa sucursal, lo crea.
     */
    public function addStock(Product $product, int $quantity, int $branchId): void
    {
        // Usamos la relación definida en el modelo Product
        $productBranch = $product->productBranches()->updateOrCreate(
            ['branch_id' => $branchId], // Si existe esta sucursal para este producto...
            [
                // Si no existe, se crea con estos valores por defecto:
                'status' => ProductStatus::Available,
                // 'stock' se inicializa en 0 por defecto en DB, o puedes forzarlo aquí si es nuevo
            ]
        );

        // increment() es atómico a nivel de base de datos (SET stock = stock + quantity)
        $productBranch->increment('stock', $quantity);
    }
}
