<?php

namespace App\Services\Catalogs;

use App\Models\Provider;
use App\Models\ProviderCatalog;
use App\Models\ProviderCatalogItem;
use App\Models\ProviderCatalogSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CatalogProcessingService
{
    /** Locks follow the same provider-first order as refresh, processing and paginated jobs. */
    public function clear(): void
    {
        DB::transaction(function () {
            Provider::withTrashed()->select('id')->orderBy('id')->lockForUpdate()->get();
            // Removing previews invalidates tokens; late jobs only update existing tokens.
            ProviderCatalog::query()->delete();
            ProviderCatalogItem::query()->delete();
            ProviderCatalogSnapshot::query()->delete();
        });
    }

    /** Preserve the reviewed response atomically; repeated processing of the same token is idempotent. */
    public function process(int $providerId, string $token, int $userId): ProviderCatalogSnapshot
    {
        return DB::transaction(function () use ($providerId, $token, $userId) {
            $provider = Provider::whereKey($providerId)->lockForUpdate()->firstOrFail();
            $existing = ProviderCatalogSnapshot::where('provider_id', $providerId)->where('query_token', $token)->first();
            if ($existing) {
                return $existing;
            }
            $catalog = ProviderCatalog::where('provider_id', $providerId)->where('token', $token)->lockForUpdate()->first();
            if (! $catalog || ! hash_equals($catalog->token, $token)
                || ! in_array($catalog->source_url, $provider->catalogUrls(), true)
                || ! in_array($catalog->status, ['partial', 'complete'], true)
                || ! $catalog->completed_at || empty($catalog->products)) {
                throw ValidationException::withMessages(['catalog' => 'La consulta cambió o no tiene productos listos para procesar. Consultá y revisá nuevamente el resultado.']);
            }
            $snapshot = ProviderCatalogSnapshot::create([
                'provider_id' => $providerId, 'provider_name' => $provider->business_name,
                'query_token' => $token, 'source_url' => $catalog->source_url,
                'status' => $catalog->status, 'message' => $catalog->message,
                'product_count' => count($catalog->products), 'queried_at' => $catalog->completed_at,
                'processed_at' => now(), 'processed_by' => $userId,
            ]);
            $rows = [];
            foreach (array_values($catalog->products) as $position => $product) {
                if (! is_array($product)) {
                    throw ValidationException::withMessages(['catalog' => 'El resultado contiene un producto inválido. No se guardó el procesamiento.']);
                }
                $rows[] = [
                    'snapshot_id' => $snapshot->id, 'position' => $position,
                    'name' => ProductData::text($product['name'] ?? null),
                    'code' => ProductData::text($product['code'] ?? null),
                    'data' => json_encode($product, JSON_THROW_ON_ERROR),
                    'created_at' => now(), 'updated_at' => now(),
                ];
                if (count($rows) === 100) {
                    ProviderCatalogItem::insert($rows);
                    $rows = [];
                }
            }
            if ($rows !== []) {
                ProviderCatalogItem::insert($rows);
            }

            return $snapshot;
        });
    }
}
