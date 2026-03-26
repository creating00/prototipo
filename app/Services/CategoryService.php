<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function createCategory(array $data): Category
    {
        $validated = $this->validateCategoryData($data);
        return Category::create($validated);
    }

    public function getAllCategories(bool $excludeNone = true): Collection
    {
        $query = Category::select('id', 'name', 'target', 'is_system', 'created_at');

        if ($excludeNone) {
            $query->exceptTarget(\App\Enums\CategoryTarget::None);
        }

        return $query->orderBy('name')->get();
    }

    public function getCategoryById(int $id, bool $excludeNone = true): Category
    {
        $query = Category::select('id', 'name', 'target', 'is_system');

        if ($excludeNone) {
            $query->exceptTarget(\App\Enums\CategoryTarget::None);
        }

        return $query->findOrFail($id);
    }

    public function getProductCategories()
    {
        return Category::where('target', \App\Enums\CategoryTarget::Product)->get();
    }

    public function updateCategory(Category|int $category, array $data): Category
    {
        $category = $category instanceof Category
            ? $category
            : $this->getCategoryById($category, false);

        $validated = $this->validateCategoryData($data, $category->id);

        $category->update($validated);

        return $category;
    }

    public function updateTarget(Category|int $category, int $targetValue): void
    {
        $category = $category instanceof Category
            ? $category
            : $this->getCategoryById($category, false);

        $category->update(['target' => $targetValue]);
    }

    /**
     * Deletes a category using soft delete.
     *
     * - System categories cannot be deleted.
     * - Related products are detached (category_id set to null).
     * - The deletion is logical (Soft Delete), not physical.
     *
     * @param int $id
     * @return array
     * @throws \Exception
     */
    public function deleteCategory($id): array
    {
        $category = $this->getCategoryById($id);

        if ($category->is_system) {
            throw new \Exception('System categories cannot be deleted', 403);
        }

        $category->products()->update(['category_id' => null]);

        $category->delete();

        return ['message' => 'Category deleted successfully'];
    }

    public function validateCategoryData(array $data, $ignoreId = null): array
    {
        $rules = [
            'name' => 'required|string|unique:categories,name' . ($ignoreId ? ",$ignoreId" : '')
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public function getCategoriesWithProducts()
    {
        return Category::with('products')->get();
    }

    public function getAllCategoriesForDataTable(): array
    {
        $targetCases = \App\Enums\CategoryTarget::cases();

        return $this->getAllCategories(false)
            ->map(fn($category, $index) => [
                'id'         => $category->id,
                'is_system'  => (int) $category->is_system,
                'number'     => $index + 1,
                'name'       => $category->name,
                'target'     => $this->renderTargetRadios($category, $targetCases),
                'created_at' => $category->created_at->format('Y-m-d'),
            ])
            ->toArray();
    }

    private function renderTargetRadios($category, array $cases): string
    {
        return view('components.category-target-radios', [
            'cases'        => $cases,
            'categoryId'   => $category->id,
            'currentValue' => $category->target->value,
        ])->render();
    }
}
