<?php

namespace App\Models;

use App\Enums\CurrencyType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductProviderPrice extends Model
{
    protected $table = 'product_provider_prices';

    protected $fillable = [
        'branch_id',
        'provider_product_id',
        'cost_price',
        'currency',
        'effective_date',
        'end_date',
    ];

    protected $casts = [
        'cost_price'     => 'decimal:2',
        'effective_date' => 'date',
        'end_date'       => 'date',
        'currency' => CurrencyType::class,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function providerProduct(): BelongsTo
    {
        return $this->belongsTo(ProviderProduct::class);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query
            ->where('effective_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }
}
