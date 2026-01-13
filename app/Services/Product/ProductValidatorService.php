<?php

namespace App\Services\Product;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductValidatorService
{
    public function validateProductData(array $data, ?int $ignoreId = null): array
    {
        $rules = [
            'code' => 'required|string|unique:products,code' . ($ignoreId ? ",$ignoreId" : ''),
            'name' => 'required|string',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'branch_id' => 'required|exists:branches,id',
            'stock' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'status' => 'required|integer|in:1,2,3',

            // Precio de Compra
            'purchase_price_amount' => 'required|numeric|min:0',
            'purchase_price_currency' => 'required|integer|in:1,2',

            // Precio de Venta
            'sale_price_amount' => 'required|numeric|min:0',
            'sale_price_currency' => 'required|integer|in:1,2',

            // Precio Mayorista (Opcional)
            'wholesale_price_amount' => 'nullable|numeric|min:0',
            // La moneda mayorista solo es requerida si se envió el monto
            'wholesale_price_currency' => 'required_with:wholesale_price_amount|integer|in:1,2',

            'providers' => 'nullable|array',
            'providers.*' => 'exists:providers,id',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
