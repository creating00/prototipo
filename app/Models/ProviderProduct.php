<?php

namespace App\Models;

use App\Enums\ProviderProductStatus;
use App\Models\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderProduct extends Model
{
    use SoftDeletes, BelongsToBranch;

    protected $table = 'provider_products';

    protected $fillable = [
        'branch_id',
        'product_id',
        'provider_id',
        'provider_code',
        'lead_time_days',
        'status',
    ];

    protected $casts = [
        'status' => ProviderProductStatus::class,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones (cambio de prueba)
    |--------------------------------------------------------------------------
    */

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductProviderPrice::class);
    }

    public function currentPrice()
    {
        return $this->hasOne(ProductProviderPrice::class)
            ->where('effective_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->latest('effective_date');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProviderProductStatus::ACTIVE);
    }
}
