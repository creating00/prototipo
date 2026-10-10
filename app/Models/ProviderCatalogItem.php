<?php

namespace App\Models;

use App\Services\Catalogs\PublicUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderCatalogItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(ProviderCatalogSnapshot::class, 'snapshot_id');
    }

    public function comparisonOption(): array
    {
        return [
            'value' => (string) $this->id,
            'text' => $this->data['name'] ?? 'Nombre no informado',
            'provider' => $this->snapshot->provider_name,
            'optgroup' => (string) $this->snapshot->provider_id,
            'code' => $this->data['code'] ?? null,
            'price' => $this->data['price'] ?? null,
            'currency' => $this->data['currency'] ?? null,
            'image' => PublicUrl::displayLink($this->data['image'] ?? null),
            'queried_at' => $this->snapshot->queried_at->format('d/m/Y H:i:s'),
            'snapshot_id' => $this->snapshot_id,
        ];
    }
}
