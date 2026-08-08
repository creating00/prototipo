<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceModification extends Model
{
    protected $fillable = [
        'branch_id',
        'user_id',
        'product_id',
        'original_price',
        'modified_price',
        'sale_id',
        'reason'
    ];

    // Relaciones cortas y concisas
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function scopeForBranch($query, string|int|array|null $branchId = null)
    {
        if (is_array($branchId)) {
            return $query->whereIn('branch_id', $branchId);
        }

        if ($branchId !== null && $branchId !== '' && $branchId !== 'all') {
            return $query->where('branch_id', (int) $branchId);
        }

        return $query;
    }
}
