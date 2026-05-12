<?php

namespace App\Models;

use App\Enums\CurrencyType;
use App\Enums\RepairType;
use App\Enums\SaleType;
use App\Models\Concerns\HasCurrency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleItem extends Model
{
    use HasCurrency;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'currency',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'currency' => CurrencyType::class,
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
