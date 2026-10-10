<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderCatalog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['products' => 'array', 'requested_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
