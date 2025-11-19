<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

class ProductStockService
{
    public function reserve(Product $product, int $qty): void
    {
        if ($product->stock < $qty) {
            throw ValidationException::withMessages([
                'stock' => 'Not enough stock'
            ]);
        }

        $product->stock = $product->stock - $qty;
        $product->save();
    }

    public function release(Product $product, int $qty): void
    {
        $product->stock = $product->stock + $qty;
        $product->save();
    }
}
