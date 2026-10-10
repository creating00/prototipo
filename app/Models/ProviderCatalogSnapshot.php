<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderCatalogSnapshot extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['queried_at' => 'datetime', 'processed_at' => 'datetime'];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class)->withTrashed();
    }

    public function products(): HasMany
    {
        return $this->hasMany(ProviderCatalogItem::class, 'snapshot_id');
    }
}
