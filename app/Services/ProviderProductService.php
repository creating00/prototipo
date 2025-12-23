<?php

namespace App\Services;

use App\Models\ProviderProduct;
use App\Enums\ProviderProductStatus;
use App\Traits\AuthTrait;
use Illuminate\Validation\ValidationException;

class ProviderProductService
{
    use AuthTrait;

    public function attachProductToProvider(array $data): ProviderProduct
    {
        if (!isset($data['branch_id'])) {
            throw new \InvalidArgumentException('El ID de la sucursal es requerido.');
        }

        // Validamos existencia solo dentro de la sucursal actual
        $exists = ProviderProduct::where('provider_id', $data['provider_id'])
            ->where('product_id', $data['product_id'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'product_id' => 'El proveedor ya tiene asociado este producto en esta sucursal.',
            ]);
        }

        return ProviderProduct::create([
            'branch_id'      => $data['branch_id'],
            'provider_id'    => $data['provider_id'],
            'product_id'     => $data['product_id'],
            'provider_code'  => $data['provider_code'] ?? null,
            'lead_time_days' => $data['lead_time_days'] ?? null,
            'status'         => ProviderProductStatus::ACTIVE,
        ]);
    }

    public function updateProviderProduct(ProviderProduct $providerProduct, array $data): ProviderProduct
    {
        $providerProduct->update([
            'product_id' => $data['product_id'],
            'provider_code' => $data['provider_code'] ?? null,
            'lead_time_days' => $data['lead_time_days'] ?? null,
            'status' => $data['status'] ?? $providerProduct->status,
        ]);

        return $providerProduct;
    }

    public function getProductsForProvider(int $providerId)
    {
        return ProviderProduct::where('provider_id', $providerId)
            ->with([
                'product',
                'currentPrice',
            ])
            ->orderBy('created_at')
            ->get();
    }

    public function deactivate(int $id): ProviderProduct
    {
        $providerProduct = ProviderProduct::findOrFail($id);
        $providerProduct->update(['status' => ProviderProductStatus::INACTIVE]);

        return $providerProduct;
    }

    public function activate(int $id): ProviderProduct
    {
        $providerProduct = ProviderProduct::findOrFail($id);
        $providerProduct->update(['status' => ProviderProductStatus::ACTIVE]);

        return $providerProduct;
    }

    public function getActiveByProvider(int $providerId)
    {
        return ProviderProduct::active()
            ->where('provider_id', $providerId)
            ->with(['product', 'currentPrice'])
            ->get();
    }
}
