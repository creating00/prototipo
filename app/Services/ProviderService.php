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
                    'name' => "{$pp->product->name} ".($pp->provider_code ? "({$pp->provider_code})" : '(S/C)'),
                    'price' => $pp->currentPrice->cost_price ?? 0,
                    'currency' => $pp->currentPrice->currency?->value ?? null,
                ];
            });
    }

    public function updateProvider($id, array $data): Provider
    {
        $provider = $this->getProviderById($id);
        if (array_intersect(['catalog_url', 'catalog_urls', 'catalog_urls_text'], array_keys($data)) !== []) {
            if (! array_key_exists('catalog_url', $data)) {
                $data['catalog_url'] = $provider->catalog_url;
            }
            if (! array_key_exists('catalog_urls', $data) && ! array_key_exists('catalog_urls_text', $data)) {
                $data['catalog_urls'] = $provider->catalog_urls ?? [];
            }
        }
        $validated = $this->validateProviderData($data, $provider->id);

        $provider->update($validated);
        if ($provider->wasChanged(['catalog_url', 'catalog_urls'])) {
            \App\Models\ProviderCatalog::where('provider_id', $provider->id)
                ->whereNotIn('source_url', $provider->catalogUrls())->delete();
        }

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
        if (array_key_exists('catalog_urls_text', $data)) {
            if (! is_string($data['catalog_urls_text']) && $data['catalog_urls_text'] !== null) {
                throw ValidationException::withMessages(['catalog_urls_text' => 'Ingresá una URL por línea.']);
            }
            if (strlen($data['catalog_urls_text'] ?? '') > 41000) {
                throw ValidationException::withMessages(['catalog_urls_text' => 'La lista de URLs es demasiado larga.']);
            }
            $data['catalog_urls'] = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $data['catalog_urls_text'] ?? '')), fn ($url) => $url !== ''));
        }
        $rules = [
            'business_name' => 'required|string',
            'tax_id' => 'required|string|unique:providers,tax_id'.($ignoreId ? ",$ignoreId" : ''),
            'short_name' => 'nullable|string',
            'contact_name' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'catalog_url' => ['nullable', 'string', 'max:2048', new \App\Rules\CatalogUrl],
            'catalog_urls' => ['sometimes', 'array', 'list', 'max:20'],
            'catalog_urls.*' => ['required', 'string', 'max:2048', 'distinct:strict', new \App\Rules\CatalogUrl],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $urls = array_filter([$data['catalog_url'] ?? null, ...($data['catalog_urls'] ?? [])]);
        if (count($urls) > 20 || count(array_unique($urls)) !== count($urls)) {
            throw ValidationException::withMessages(['catalog_urls' => 'Guardá hasta 20 URLs diferentes en total, sin repetir la URL principal.']);
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
