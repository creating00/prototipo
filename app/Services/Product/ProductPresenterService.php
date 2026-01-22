<?php

namespace App\Services\Product;

use App\Enums\ProductStatus;
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

            $providers = $product->providers;
            $count = $providers->count();
            $providerHtml = '<span class="text-muted small">Sin proveedores</span>';

            if ($count > 0) {
                // Nombres para los spans visibles
                $displayNames = $providers->take(2)->pluck('business_name');

                // Nombres para el tooltip (todos los proveedores)
                $allNames = $providers->pluck('business_name')->implode("\n");

                $htmlParts = $displayNames->map(
                    fn($name) =>
                    "<span class='d-block small text-truncate' style='line-height: 1.2; max-width: 150px;'>" . e($name) . "</span>"
                );

                if ($count > 2) {
                    $extra = $count - 2;
                    // Agregamos el atributo title con la lista completa
                    $htmlParts->push(
                        "<span class='text-primary small fw-bold cursor-help' title='" . e($allNames) . "'>+{$extra} más</span>"
                    );
                }

                $providerHtml = $htmlParts->implode('');
            }

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
                'provider' => $providerHtml,
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

    public function formatForSummaryByBranch(Collection $products): Collection
    {
        return $products->map(function ($product) {
            return [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'description' => $product->description,
                'image' => $product->full_image_url,
                'category_id' => $product->category_id,
                'category' => $product->category?->name,
                'average_rating' => $product->average_rating,
                'branches' => $product->productBranches->map(function ($branch) {
                    return [
                        'branch_id' => $branch->branch_id,
                        'branch_name' => $branch->branch->name,
                        'stock' => $branch->stock,
                        'status' => $branch->status?->value,
                        'prices' => $branch->prices->mapWithKeys(
                            fn($price) => [
                                $price->type->name => [
                                    'amount' => $price->amount,
                                    'currency' => $price->currency->value,
                                    'formatted' => $price->getFormattedAmount(),
                                ]
                            ]
                        ),
                    ];
                }),
            ];
        });
    }

    public function formatForSummaryByBranchLite(Collection $products): Collection
    {
        return $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'category_id' => $product->category_id,
                'category' => $product->category?->name,
                'average_rating' => $product->average_rating,
                'branches' => $product->productBranches->mapWithKeys(function ($branch) {
                    return [
                        $branch->branch_id => [
                            'stock' => $branch->stock,
                            'status' => $branch->status?->value,
                            'prices' => $branch->prices->mapWithKeys(
                                fn($price) => [$price->type->name => $price->amount]
                            ),
                        ]
                    ];
                }),
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

    private function resolveProductStatusBadge(?ProductStatus $status): string
    {
        if (!$status) {
            return '<span class="badge-custom badge-custom-gray">N/A</span>';
        }

        return sprintf(
            '<span class="%s">%s</span>',
            $status->badgeClass(),
            $status->label()
        );
    }
}
