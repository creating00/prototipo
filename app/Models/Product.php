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

    protected $casts = [
        'status' => \App\Enums\ProductStatus::class,
    ];

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
            if (!$branchId) {
                return null; // no hay sucursal asignada
            }
        }

        if (isset($this->branchCache[$branchId])) {
            return $this->branchCache[$branchId];
        }

        // Optimization: Use already loaded relationship if available
        if ($this->relationLoaded('productBranches')) {
            $branch = $this->productBranches->firstWhere('branch_id', $branchId);
            if ($branch) {
                return $this->branchCache[$branchId] = $branch;
            }
        }

        return $this->branchCache[$branchId] = $this->productBranches()
            ->where('branch_id', $branchId)
            ->first();
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

        // Optimization: Use already loaded relationship if available
        if ($branchModel->relationLoaded('prices')) {
            return $branchModel->prices
                ->filter(function ($price) use ($type, $currency) {
                    $match = $price->type === $type;
                    if ($currency) {
                        $match = $match && $price->currency === $currency;
                    }
                    return $match;
                })
                ->first();
        }

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

    public function getFullImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        // Si ya es una URL completa (http:// o https://)
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        // Si es una ruta local (/storage/)
        if (str_starts_with($this->image, '/storage/')) {
            return config('app.url') . $this->image;
        }

        // Si es una ruta relativa sin /storage/
        if (str_starts_with($this->image, 'storage/')) {
            return config('app.url') . '/' . $this->image;
        }

        return $this->image;
    }
}
