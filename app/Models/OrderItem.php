<?php

namespace App\Models;

use App\Enums\CurrencyType;
use App\Models\Concerns\HasCurrency;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasCurrency;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'currency',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'currency' => CurrencyType::class,
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
