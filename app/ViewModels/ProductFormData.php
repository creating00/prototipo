<?php

namespace App\ViewModels;

use App\Enums\CurrencyType;
use App\Enums\PriceType;
use App\Models\Product;
use App\Models\ProductBranch;
use Illuminate\Support\Collection;

class ProductFormData
{
    public function __construct(
        public readonly ?Product $product,
        public readonly ProductBranch $productBranch,
        public readonly array $statusOptions,
        public readonly array $currencyOptions,
        public readonly Collection $branches,
        public readonly Collection $categories,
        public readonly Collection $provinces,
        public readonly int $branchUserId,
        public readonly bool $isAdmin = false,
    ) {}

    public function price(int $type, string $key = 'amount'): string|int|null
    {
        $price = $this->productBranch
            ->prices
            ->firstWhere('type', $type);

        if (! $price) {
            return null;
        }

        $value = $price->{$key};

        // Si es enum (CurrencyType), devolver su value
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        return $value;
    }

    public function currency(int $type): int
    {
        return $this->price($type, 'currency')
            ?? match ($type) {
                PriceType::PURCHASE->value => CurrencyType::USD->value,
                default => CurrencyType::ARS->value,
            };
    }
}
