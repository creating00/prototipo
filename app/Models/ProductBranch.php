<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBranch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'branch_id',
        'stock',
        'low_stock_threshold',
        'status'
    ];

    protected $casts = [
        'stock' => 'integer',
        'low_stock_threshold' => 'integer',
        'status' => ProductStatus::class,
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductBranchPrice::class);
    }

    public function getPriceByType($type, $currency)
    {
        return $this->prices()
            ->where('type', $type)
            ->where('currency', $currency)
            ->first();
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', ProductStatus::Available);
    }
}
