<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductBranchPrice;
use Illuminate\Support\Collection;

class ProductPresenterService
{
    public function formatForDataTable(Collection $products, int $branchId): array
    {
        return $products->map(function ($product, $index) use ($branchId) {
            $purchaseModel = $product->purchasePriceModel($branchId);
            $saleModel = $product->salePriceModel($branchId);

            return [
                'id' => $product->id,
                'number' => $index + 1,
                'code' => $product->code,
                'name' => $product->name,
                'purchase_price' => $this->formatPriceModel($purchaseModel, 'fw-bold text-primary'),
                'sale_price' => $this->formatPriceModel($saleModel, 'fw-bold text-success'),
                'purchase_price_raw' => $purchaseModel?->amount,
                'sale_price_raw' => $saleModel?->amount,
                'stock' => $product->getStock($branchId),
                'status' => $this->resolveProductStatusBadge($product->getStatus($branchId)),
            ];
        })->toArray();
    }

    public function formatForSummary(Collection $products, ?int $branchId = null): Collection
    {
        return $products->map(function ($product) use ($branchId) {
            $branch = $product->productBranches->firstWhere('branch_id', $branchId);

            return [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'description' => $product->description,
                'image' => $product->image,
                'category' => $product->category?->name,
                'average_rating' => $product->average_rating,
                'stock' => $branch?->stock ?? 0,
                'status' => $branch?->status?->value ?? null,
                'prices' => $branch
                    ? $branch->prices->mapWithKeys(fn($price) => [$price->type->name => $price->amount])
                    : []
            ];
        });
    }

    private function formatPriceModel(?ProductBranchPrice $model, string $class = ''): string
    {
        if (!$model) {
            return '<span class="text-muted">-</span>';
        }

        $currency = $model->currency;
        $symbol = $currency->symbol();
        $formatted = number_format($model->amount, 2, ',', '.');

        return sprintf(
            '<span class="%s">%s %s</span>',
            $class,
            $symbol,
            $formatted
        );
    }

    private function resolveProductStatusBadge(?\App\Enums\ProductStatus $status): string
    {
        if (!$status) {
            return '<span class="badge-custom badge-custom-gray">N/A</span>';
        }

        $badgeClass = match ($status) {
            \App\Enums\ProductStatus::Available => 'badge-custom badge-custom-green',
            \App\Enums\ProductStatus::OutOfStock => 'badge-custom badge-custom-red',
            \App\Enums\ProductStatus::Discontinued => 'badge-custom badge-custom-gray',
        };

        return "<span class=\"{$badgeClass}\">{$status->label()}</span>";
    }
}
