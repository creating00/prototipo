<?php

namespace App\Services;

use App\Models\ProviderProduct;
use App\Enums\ProviderProductStatus;
use Illuminate\Validation\ValidationException;

class ProviderProductService
{
    public function attachProductToProvider(array $data): ProviderProduct
    {
        $exists = ProviderProduct::where('provider_id', $data['provider_id'])
            ->where('product_id', $data['product_id'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'product_id' => 'El proveedor ya tiene asociado este producto.',
            ]);
        }

        return ProviderProduct::create([
            'provider_id'     => $data['provider_id'],
            'product_id'      => $data['product_id'],
            'provider_code'   => $data['provider_code'] ?? null,
            'lead_time_days'  => $data['lead_time_days'] ?? null,
            'status'          => ProviderProductStatus::ACTIVE,
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
