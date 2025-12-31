<?php

namespace App\Services;

use App\Models\Provider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProviderService
{
    public function createProvider(array $data): Provider
    {
        $validated = $this->validateProviderData($data);
        return Provider::create($validated);
    }

    public function getAllProviders()
    {
        return Provider::orderBy('business_name')->get();
    }

    public function getProviderById($id): Provider
    {
        return Provider::findOrFail($id);
    }

    // App\Services\ProviderService.php

    public function getProviderProducts(int $providerId)
    {
        $provider = Provider::findOrFail($providerId);

        return $provider->providerProducts()
            ->with(['product', 'currentPrice'])
            ->get()
            ->map(function ($pp) {
                return [
                    'id' => $pp->id,
                    'name' => "{$pp->product->name} " . ($pp->provider_code ? "({$pp->provider_code})" : "(S/C)"),
                    'price' => $pp->currentPrice->cost_price ?? 0,
                    'currency' => $pp->currentPrice->currency?->value ?? null,
                ];
            });
    }

    public function updateProvider($id, array $data): Provider
    {
        $provider = $this->getProviderById($id);
        $validated = $this->validateProviderData($data, $provider->id);

        $provider->update($validated);
        return $provider->fresh();
    }

    public function deleteProvider($id): array
    {
        $provider = $this->getProviderById($id);

        $provider->delete();
        return ['message' => 'Provider deleted'];
    }

    public function validateProviderData(array $data, $ignoreId = null): array
    {
        $rules = [
            'business_name' => 'required|string',
            'tax_id' => 'required|string|unique:providers,tax_id' . ($ignoreId ? ",$ignoreId" : ''),
            'short_name' => 'nullable|string',
            'contact_name' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public function getAllProvidersForDataTable()
    {
        $providers = $this->getAllProviders();

        return $providers->map(function ($provider, $index) {
            return [
                'id' => $provider->id,
                'number' => $index + 1,
                'business_name' => $provider->business_name,
                'tax_id' => $provider->tax_id,
                // 'short_name' => $provider->short_name,
                'contact_name' => $provider->contact_name,
                // 'email' => $provider->email,
                'phone' => $provider->phone,
                'address' => \Illuminate\Support\Str::limit($provider->address, 30),
                'created_at' => $provider->created_at->format('Y-m-d'),
            ];
        })->toArray();
    }
}
