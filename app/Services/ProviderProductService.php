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
        // Buscamos si ya existe la relación
        $providerProduct = ProviderProduct::where('product_id', $data['product_id'])
            ->where('provider_id', $data['provider_id'])
            ->first();

        if ($providerProduct) {
            // OPCIONAL: Si quieres que registros viejos se vuelvan globales al tocarlos
            if ($providerProduct->branch_id !== null) {
                $providerProduct->update(['branch_id' => null]);
            }
            return $providerProduct;
        }

        return ProviderProduct::create([
            'branch_id'      => null,
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
