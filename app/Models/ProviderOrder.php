<?php

namespace App\Models;

use App\Enums\ProviderOrderStatus;
use App\Models\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderOrder extends Model
{
    use BelongsToBranch;
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

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopePending($query)
    {
        return $query->where('status', ProviderOrderStatus::PENDING);
    }
}
