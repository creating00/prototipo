<?php

namespace App\Models;

use App\Enums\CurrencyType;
use App\Enums\PriceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBranchPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_branch_id',
        'type',
        'amount',
        'currency'
    ];

    protected $casts = [
        'amount' => 'float',
        'type' => PriceType::class,
        'currency' => CurrencyType::class,
    ];

    public function productBranch(): BelongsTo
    {
        return $this->belongsTo(ProductBranch::class);
    }

    public function getFormattedAmount(): string
    {
        $symbol = $this->currency->symbol();

        $formatted = number_format($this->amount, 2, ',', '.');

        return "{$symbol} {$formatted}";
    }
}
