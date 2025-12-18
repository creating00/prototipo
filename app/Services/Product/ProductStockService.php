<?php

namespace App\Services\Product;

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
}
