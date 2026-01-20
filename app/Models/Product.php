<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\CurrencyType;
use App\Enums\PriceType;
use App\Models\Traits\PriceFormattingTrait;
use App\Traits\AuthTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory, SoftDeletes, PriceFormattingTrait, AuthTrait;

    protected $fillable = [
        'code',
        'name',
        'image',
        'description',
        'category_id'
    ];

    protected array $branchCache = [];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function productBranches(): HasMany
    {
        return $this->hasMany(ProductBranch::class);
    }

    public function providers()
    {
        // Relación de muchos a muchos a través de la tabla pivote
        return $this->belongsToMany(Provider::class, 'provider_products')
            ->withPivot(['provider_code', 'lead_time_days', 'status'])
            ->withTimestamps()
            ->wherePivot('deleted_at', null);
    }

    /*
    |--------------------------------------------------------------------------
    | Branch contextual helpers
    |--------------------------------------------------------------------------
    */

    public function branchContext(?int $branchId = null): ?ProductBranch
    {
        if (!$branchId) {
            $branchId = $this->currentBranchId();
            //$branchId = auth()->user()->branch_id;
            if (!$branchId) {
                return null; // no hay sucursal asignada
            }
        }

        if (!isset($this->branchCache[$branchId])) {
            $this->branchCache[$branchId] = $this->productBranches()
                ->where('branch_id', $branchId)
                ->first();
        }

        return $this->branchCache[$branchId];
    }

    /*
    |--------------------------------------------------------------------------
    | Price getters (simple values)
    |--------------------------------------------------------------------------
    */

    public function price(?int $branchId, PriceType $type, ?CurrencyType $currency = null): ?float
    {
        return $this->priceModel($branchId, $type, $currency)?->amount;
    }

    public function purchasePrice(?int $branchId = null, ?CurrencyType $currency = null): ?float
    {
        return $this->price($branchId, PriceType::PURCHASE, $currency);
    }

    public function salePrice(?int $branchId = null, ?CurrencyType $currency = null): ?float
    {
        return $this->price($branchId, PriceType::SALE, $currency);
    }

    public function wholesalePrice(?int $branchId = null, ?CurrencyType $currency = null): ?float
    {
        return $this->price($branchId, PriceType::WHOLESALE, $currency);
    }

    public function repairPrice(?int $branchId = null, ?CurrencyType $currency = null): ?float
    {
        return $this->price($branchId, PriceType::REPAIR, $currency);
    }

    /*
    |--------------------------------------------------------------------------
    | Price getters (full model)
    |--------------------------------------------------------------------------
    */

    public function priceModel(?int $branchId, PriceType $type, ?CurrencyType $currency = null): ?ProductBranchPrice
    {
        $branchModel = $this->branchContext($branchId);
        if (!$branchModel) return null;

        $query = $branchModel->prices()->where('type', $type->value);

        // Si pasamos moneda, filtramos. Si no, traerá el primero que encuentre (sea ARS o USD)
        if ($currency) {
            $query->where('currency', $currency->value);
        }

        return $query->first();
    }

    public function purchasePriceModel(?int $branchId = null, ?CurrencyType $currency = null): ?ProductBranchPrice
    {
        return $this->priceModel($branchId, PriceType::PURCHASE, $currency);
    }

    public function salePriceModel(?int $branchId = null, ?CurrencyType $currency = null): ?ProductBranchPrice
    {
        return $this->priceModel($branchId, PriceType::SALE, $currency);
    }

    public function wholesalePriceModel(?int $branchId = null, ?CurrencyType $currency = null): ?ProductBranchPrice
    {
        return $this->priceModel($branchId, PriceType::WHOLESALE, $currency);
    }

    public function repairPriceModel(?int $branchId = null, ?CurrencyType $currency = null): ?ProductBranchPrice
    {
        return $this->priceModel($branchId, PriceType::REPAIR, $currency);
    }

    /*
    |--------------------------------------------------------------------------
    | Stock & status
    |--------------------------------------------------------------------------
    */

    public function getStock(?int $branchId = null): int
    {
        return $this->branchContext($branchId)?->stock ?? 0;
    }

    public function getStatus(?int $branchId = null): ?ProductStatus
    {
        return $this->branchContext($branchId)?->status;
    }

    /*
    |--------------------------------------------------------------------------
    | Ratings
    |--------------------------------------------------------------------------
    */

    public function getAverageRatingAttribute(): float
    {
        return $this->ratings()->avg('rate') ?? 0;
    }
}
