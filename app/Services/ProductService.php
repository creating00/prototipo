<?php

namespace App\Services;

use App\Models\Product;
use App\Services\Product\ProductBranchService;
use App\Services\Product\ProductPresenterService;
use App\Services\Product\ProductValidatorService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use App\Traits\AuthTrait;

class ProductService
{
    use AuthTrait;

    public function __construct(
        protected ProductBranchService $branchService,
        protected ImageService $imageService,
        protected ProductValidatorService $validatorService,
        protected ProductPresenterService $presenterService
    ) {}

    public function create(array $data, ?UploadedFile $imageFile = null, ?string $imageUrl = null): Product
    {
        $validated = $this->validatorService->validateProductData($data);
        $validated['image'] = $this->handleImageUpload($imageFile, $imageUrl, $validated['code']);

        $product = Product::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image' => $validated['image'],
            'category_id' => $validated['category_id'] ?? null,
        ]);

        $this->branchService->createBranchDataForProduct($product, $validated);

        return $product->fresh();
    }

    public function update(
        Product $product,
        array $data,
        ?UploadedFile $imageFile = null,
        ?string $imageUrl = null,
        bool $removeImage = false
    ): Product {
        $validated = $this->validatorService->validateProductData($data, $product->id);
        $newImage = $this->handleImageUpdate($product, $validated, $imageFile, $imageUrl, $removeImage);

        $product->update([
            'code' => $validated['code'] ?? $product->code,
            'name' => $validated['name'] ?? $product->name,
            'description' => $validated['description'] ?? $product->description,
            'image' => $newImage,
            'category_id' => $validated['category_id'] ?? $product->category_id,
        ]);

        if (isset($validated['branch_id'])) {
            $this->branchService->updateBranchDataForProduct($product, $validated);
        }

        return $product->fresh();
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function getById(int $id): Product
    {
        return Product::findOrFail($id);
    }

    public function getProductById(int $id): Product
    {
        return $this->getById($id);
    }

    public function getProductForEdit(int $productId, int $branchId): Product
    {
        return Product::with([
            'category',
            'productBranches' => function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            },
            'productBranches.prices' => function ($query) use ($branchId) {
                $query->whereHas('productBranch', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            }
        ])->findOrFail($productId);
    }

    public function getProductWithAllBranches(int $id): Product
    {
        return Product::with([
            'category',
            'productBranches',
            'productBranches.prices'
        ])->findOrFail($id);
    }

    public function getAll(): Collection
    {
        return Product::with(['category', 'ratings', 'productBranches.prices'])->get();
    }

    public function getAllForSummary(?int $branchId = null): Collection
    {
        $branchId = $branchId ?? $this->currentBranchId();
        $products = Product::with(['category', 'ratings', 'productBranches.prices'])->get();

        return $this->presenterService->formatForSummary($products, $branchId);
    }

    public function getAllForDataTable(): array
    {
        $branchId = $branchId ?? $this->currentBranchId();
        $products = Product::with([
            'category',
            'productBranches' => fn($q) => $q->where('branch_id', $branchId),
            'productBranches.prices',
        ])->get();

        return $this->presenterService->formatForDataTable($products, $branchId);
    }

    private function handleImageUpload(?UploadedFile $imageFile, ?string $imageUrl, string $productCode): ?string
    {
        if ($imageUrl) {
            return $this->imageService->validateAndStoreExternalImage($imageUrl, $productCode);
        }

        if ($imageFile) {
            return $this->imageService->processAndStoreImage($imageFile, $productCode);
        }

        return null;
    }

    private function handleImageUpdate(
        Product $product,
        array $validated,
        ?UploadedFile $imageFile,
        ?string $imageUrl,
        bool $removeImage
    ): ?string {
        if ($removeImage) {
            $this->imageService->deleteImageIfLocal($product->image);
            return null;
        }

        if ($imageUrl) {
            $this->imageService->deleteImageIfLocal($product->image);
            return $this->imageService->validateAndStoreExternalImage($imageUrl, $validated['code'] ?? $product->code);
        }

        if ($imageFile) {
            $this->imageService->deleteImageIfLocal($product->image);
            return $this->imageService->processAndStoreImage($imageFile, $validated['code'] ?? $product->code);
        }

        return $product->image;
    }
}
