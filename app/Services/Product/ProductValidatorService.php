<?php

namespace App\Services\Product;

use App\Enums\ProductStatus;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class ProductValidatorService
{
    public function validateProductData(array $data, ?int $ignoreId = null): array
    {
        if (isset($data['category_id']) && ($data['category_id'] === '' || $data['category_id'] === 'null')) {
            $data['category_id'] = null;
        }

        if (isset($data['status'])) {
            $data['status'] = is_numeric($data['status']) ? (int)$data['status'] : $data['status'];
        }

        if (isset($data['purchase_price_currency'])) {
            $data['purchase_price_currency'] = (int)$data['purchase_price_currency'];
        }

        if (isset($data['sale_price_currency'])) {
            $data['sale_price_currency'] = (int)$data['sale_price_currency'];
        }

        if (isset($data['wholesale_price_currency']) && $data['wholesale_price_currency'] !== '') {
            $data['wholesale_price_currency'] = (int)$data['wholesale_price_currency'];
        }

        if (isset($data['repair_price_currency']) && $data['repair_price_currency'] !== '') {
            $data['repair_price_currency'] = (int)$data['repair_price_currency'];
        }

        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $isProvincialAdmin = $user && $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value);
        $hasPriceInputs = isset($data['purchase_price_amount']) || isset($data['sale_price_amount']);

        // Si los campos de precio no se enviaron o el usuario no es provincial_admin, los precios son nulos/opcionales
        $priceRequiredRule = ($isProvincialAdmin && ($hasPriceInputs || !$ignoreId)) ? 'required' : 'nullable';

        $isConsolidated = (isset($data['branch_id']) && $data['branch_id'] === 'all') || !isset($data['stock']);
        $branchRule = (isset($data['branch_id']) && $data['branch_id'] === 'all')
            ? 'required'
            : 'required|exists:branches,id';

        $rules = [
            'code' => 'required|string|unique:products,code' . ($ignoreId ? ",$ignoreId" : ''),
            'name' => 'required|string',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'imageFile' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'imageUrl' => 'nullable|url',
            'branch_id' => $branchRule,
            'stock' => $isConsolidated ? 'nullable|integer|min:0' : 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'status' => $isConsolidated ? ['nullable', new Enum(ProductStatus::class)] : ['required', new Enum(ProductStatus::class)],

            // Precio de Compra
            'purchase_price_amount' => $priceRequiredRule . '|numeric|min:0',
            'purchase_price_currency' => $priceRequiredRule . '|integer|in:1,2',

            // Precio de Venta
            'sale_price_amount' => $priceRequiredRule . '|numeric|min:0',
            'sale_price_currency' => $priceRequiredRule . '|integer|in:1,2',

            // Precio Mayorista (Opcional)
            'wholesale_price_amount' => 'nullable|numeric|min:0',
            'wholesale_price_currency' => 'required_with:wholesale_price_amount|integer|in:1,2',

            // Precio de Reparación (Opcional)
            'repair_price_amount' => 'nullable|numeric|min:0',
            'repair_price_currency' => 'required_with:repair_price_amount|integer|in:1,2',

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
