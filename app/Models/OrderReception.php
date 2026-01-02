<?php

namespace App\Models;

use App\Enums\OrderReceptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReception extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'status',
        'received_at',
        'observation'
    ];

    protected $casts = [
        'status' => OrderReceptionStatus::class,
        'received_at' => 'datetime',
    ];

    // Relación con el pedido original
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Usuario que realizó la recepción
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
