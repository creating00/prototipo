<?php

namespace App\Services\Product;

use App\Enums\ProductStatus;
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
            'status'              => $this->resolveStatus($data),
        ]);

        $this->priceService->createPricesForBranch($branch, $data);

        return $branch;
    }

    public function updateOrCreateBranchData(Product $product, array $data): ProductBranch
    {
        // Buscamos el registro incluyendo los borrados (Soft Deletes)
        $branch = $product->productBranches()
            ->withTrashed()
            ->where('branch_id', $data['branch_id'])
            ->first();

        if ($branch) {
            if ($branch->trashed()) {
                $branch->restore(); // Si estaba borrado, lo traemos de vuelta
            }

            $branch->update([
                'stock'               => $data['stock'] ?? 0,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
                'status'              => $this->resolveStatus($data),
            ]);
        } else {
            // Si realmente no existe ni en la papelera, lo creamos
            $branch = $product->productBranches()->create([
                'branch_id'           => $data['branch_id'],
                'stock'               => $data['stock'],
                'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
                'status'              => $this->resolveStatus($data),
            ]);
        }

        $this->priceService->updatePricesForBranch($branch, $data);

        return $branch;
    }

    public function updateBranchDataForProduct(Product $product, array $data): ProductBranch
    {
        $branch = $product->productBranches()->updateOrCreate(
            ['branch_id' => $data['branch_id']],
            [
                'stock'               => $data['stock'] ?? 0,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
                'status'              => $this->resolveStatus($data),
            ]
        );

        $this->priceService->updatePricesForBranch($branch, $data);

        return $branch;
    }

    private function resolveStatus(array $data): ProductStatus
    {
        if (isset($data['stock']) && (int)$data['stock'] === 0) {
            return ProductStatus::OutOfStock;
        }

        if (isset($data['status']) && $data['status'] instanceof ProductStatus) {
            return $data['status'];
        }

        if (isset($data['status'])) {
            $val = is_numeric($data['status']) ? (int)$data['status'] : $data['status'];
            return ProductStatus::tryFrom($val) ?? ProductStatus::Available;
        }

        return ProductStatus::Available;
    }

    public function deleteBranchData(Product $product, int $branchId): bool
    {
        $productBranch = $product->productBranches()
            ->where('branch_id', $branchId)
            ->first();

        if ($productBranch) {
            $productBranch->prices()->delete();
            $productBranch->delete();
            return true;
        }

        return false;
    }
}
