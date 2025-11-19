<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'code',
        'name',
        'description',
        'stock',
        'branch_id',
        'purchase_price',
        'sale_price',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
