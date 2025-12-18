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

    public function deleteCategory($id): array
    {
        $category = $this->getCategoryById($id);

        // Verificar si tiene productos asociados
        if ($category->products()->count() > 0) {
            throw new \Exception('Cannot delete a category with associated products', 400);
        }

        $category->delete();
        return ['message' => 'Category deleted'];
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
                'number' => $index + 1,                   // Número incremental visible
                'name' => $category->name,                // Visible
                'created_at' => $category->created_at->format('Y-m-d'), // Visible
            ];
        })->toArray();
    }
}
