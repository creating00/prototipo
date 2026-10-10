<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderCatalog extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (self $catalog) {
            $catalog->source_key = hash('sha256', $catalog->source_url);
        });
    }

    protected function casts(): array
    {
        return ['products' => 'array', 'pagination' => 'array', 'last_activity_at' => 'datetime', 'requested_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
