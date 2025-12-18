<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductBranch;

class ProductBranchService
{
    protected ProductBranchPriceService $priceService;

    public function __construct(ProductBranchPriceService $priceService)
    {
        $this->priceService = $priceService;
    }

    public function createBranchDataForProduct(Product $product, array $data): ProductBranch
    {
        $branch = $product->productBranches()->create([
            'branch_id'           => $data['branch_id'],
            'stock'               => $data['stock'],
            'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
            'status'              => $data['status'],
        ]);

        $this->priceService->createPricesForBranch($branch, $data);

        return $branch;
    }

    public function updateBranchDataForProduct(Product $product, array $data): ProductBranch
    {
        $branch = $product->productBranches()
            ->where('branch_id', $data['branch_id'])
            ->firstOrFail();

        $branch->update([
            'stock'               => $data['stock'] ?? $branch->stock,
            'low_stock_threshold' => $data['low_stock_threshold'] ?? $branch->low_stock_threshold,
            'status'              => $data['status'] ?? $branch->status,
        ]);

        $this->priceService->updatePricesForBranch($branch, $data);

        return $branch;
    }
}
