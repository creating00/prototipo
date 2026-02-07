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

    /**
     * Resuelve el estado basado en el stock.
     */
    private function resolveStatus(array $data): ProductStatus
    {
        // Si el stock es 0, retornamos la instancia del Enum directamente
        if (isset($data['stock']) && (int)$data['stock'] === 0) {
            return ProductStatus::OutOfStock;
        }

        // Si $data['status'] ya es una instancia del Enum, la retornamos
        if ($data['status'] instanceof ProductStatus) {
            return $data['status'];
        }

        // Convertimos el string/value que viene del request a una instancia del Enum
        return ProductStatus::from($data['status']);
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
