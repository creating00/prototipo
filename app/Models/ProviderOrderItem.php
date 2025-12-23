<?php

namespace App\Models;

use App\Enums\CurrencyType;
use Illuminate\Database\Eloquent\Model;

class ProviderOrderItem extends Model
{
    protected $fillable = [
        'provider_order_id',
        'provider_product_id',
        'quantity',
        'unit_cost',
        'currency',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'currency' => CurrencyType::class,
    ];

    public function order()
    {
        return $this->belongsTo(ProviderOrder::class);
    }

    public function providerProduct()
    {
        return $this->belongsTo(ProviderProduct::class);
    }
}
