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
        'destination_branch_id',
        'stock_sent_at',
    ];

    protected $casts = [
        'currency' => CurrencyType::class,
        'stock_sent_at' => 'datetime',
        'destination_branch_id' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function destinationBranch()
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }
}
