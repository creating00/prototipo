<?php

namespace App\Models;

use App\Enums\ProviderOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderOrder extends Model
{
    protected $fillable = [
        'branch_id',
        'provider_id',
        'order_date',
        'expected_delivery_date',
        'received_date',
        'status',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'received_date' => 'date',
        'status' => ProviderOrderStatus::class,
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

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function items()
    {
        return $this->hasMany(ProviderOrderItem::class);
    }
}
