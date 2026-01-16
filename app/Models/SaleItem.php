<?php

namespace App\Models;

use App\Enums\RepairType;
use App\Enums\SaleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function descriptionForReceipt($sale): string
    {
        $description = $this->product->name ?? 'Producto';

        if ($sale->sale_type === SaleType::Repair) {
            foreach (RepairType::cases() as $repair) {
                if ($repair->categoryId() === $this->product->category_id) {
                    return "[{$repair->label()}] {$description}";
                }
            }
        }

        return $description;
    }
}
