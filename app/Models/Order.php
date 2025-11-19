<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'product_id',
        'client_id',
        'user_id',
        'quantity',
        'amount_to_charge',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
