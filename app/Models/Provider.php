<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_name',
        'tax_id',
        'short_name',
        'contact_name',
        'email',
        'phone',
        'address',
    ];

    public function providerProducts(): HasMany
    {
        return $this->hasMany(ProviderProduct::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'provider_products')
            ->withPivot([
                'id',
                'provider_code',
                'lead_time_days',
                'status',
                'deleted_at',
            ])
            ->withTimestamps();
    }
}
