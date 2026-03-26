<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function createCategory(array $data): Category
    {
        $validated = $this->validateCategoryData($data);
        return Category::create($validated);
    }

    public function getAllCategories()
    {
        return Category::orderBy('name')->get();
    }

    public function getCategoryById($id): Category
    {
        return Category::findOrFail($id);
    }

    public function updateCategory($id, array $data): Category
    {
        $category = $this->getCategoryById($id);
        $validated = $this->validateCategoryData($data, $category->id);

        $category->update($validated);
        return $category->fresh();
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

    public function getAllCategoriesForDataTable()
    {
        $categories = $this->getAllCategories();

        return $categories->map(function ($category, $index) {
            return [
                'id' => $category->id,                    // Oculto pero disponible como data-id
                'is_system' => $category->is_system ? 1 : 0,
                'number' => $index + 1,                   // Número incremental visible
                'name' => $category->name,                // Visible
                'created_at' => $category->created_at->format('Y-m-d'), // Visible
            ];
        })->toArray();
    }
}
