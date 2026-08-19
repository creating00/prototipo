<?php

namespace App\Services\Product;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductBranchPrice;
use Illuminate\Support\Collection;

class ProductPresenterService
{
    public function formatForDataTable(Collection $products, ?int $branchId = null): array
    {
        return $products->map(function ($product, $index) use ($branchId) {
            if (!$branchId) {
                $stockSum = $product->productBranches->sum('stock');
                $branchDetails = $product->productBranches->map(fn($pb) => ($pb->branch->name ?? 'Sucursal') . ': ' . $pb->stock)->implode(' · ');
                $stock = sprintf(
                    '<span class="fw-bold" title="%s">%d%s</span>',
                    e($branchDetails),
                    $stockSum,
                    $branchDetails ? ' <small class="text-muted d-block" style="font-size: 0.75rem;">' . e($branchDetails) . '</small>' : ''
                );

                $purchasePriceHtml = $this->formatConsolidatedPrice($product->productBranches, \App\Enums\PriceType::PURCHASE, 'fw-bold text-primary');
                $salePriceHtml = $this->formatConsolidatedPrice($product->productBranches, \App\Enums\PriceType::SALE, 'fw-bold text-success');

                $allPrices = $product->productBranches->flatMap(fn($pb) => $pb->prices);
                $purchaseModel = $allPrices->where('type', \App\Enums\PriceType::PURCHASE)->sortByDesc('amount')->first();
                $saleModel = $allPrices->where('type', \App\Enums\PriceType::SALE)->sortByDesc('amount')->first();

                $firstBranch = $product->productBranches->first();
                $status = $firstBranch?->status;
            } else {
                $purchaseModel = $product->purchasePriceModel($branchId);
                $saleModel = $product->salePriceModel($branchId);
                $purchasePriceHtml = $this->formatPriceModel($purchaseModel, 'fw-bold text-primary');
                $salePriceHtml = $this->formatPriceModel($saleModel, 'fw-bold text-success');
                $stock = $product->getStock($branchId);
                $status = $product->getStatus($branchId);
            }

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
                'purchase_price' => $purchasePriceHtml,
                'sale_price' => $salePriceHtml,
                'purchase_price_raw' => $purchaseModel?->amount,
                'sale_price_raw' => $saleModel?->amount,
                'stock' => $stock,
                'provider' => $providerHtml,
                'status' => $this->resolveProductStatusBadge($status),
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
                'target'   => $product->category?->target->value,
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

    private function formatConsolidatedPrice(Collection $productBranches, \App\Enums\PriceType $type, string $class = ''): string
    {
        $branchPrices = collect();

        foreach ($productBranches as $pb) {
            $branchName = $pb->branch->name ?? 'Sucursal';
            $priceModel = $pb->prices->firstWhere('type', $type);
            if ($priceModel) {
                $symbol = $priceModel->currency->symbol();
                $formatted = $symbol . ' ' . number_format($priceModel->amount, 2, ',', '.');
                $branchPrices->push([
                    'branch' => $branchName,
                    'amount' => $priceModel->amount,
                    'formatted' => $formatted,
                ]);
            }
        }

        if ($branchPrices->isEmpty()) {
            return '<span class="text-muted">-</span>';
        }

        $maxPriceItem = $branchPrices->sortByDesc('amount')->first();
        $primaryFormatted = $maxPriceItem['formatted'];
        $details = $branchPrices->map(fn($item) => "{$item['branch']}: {$item['formatted']}")->implode(' · ');

        return sprintf(
            '<span class="%s" title="%s">%s <small class="text-muted d-block fw-normal" style="font-size: 0.75rem;">%s</small></span>',
            $class,
            e($details),
            e($primaryFormatted),
            e($details)
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
