<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\BaseCategoryController;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryWebController extends BaseCategoryController
{
    public function index()
    {
        $this->authorize('viewAny', Category::class);
        $rowData = $this->categoryService->getAllCategoriesForDataTable();
        $categories = $this->categoryService->getAllCategories();

        $headers = ['#', 'Nombre', 'Creado en:'];
        $hiddenFields = ['id', 'is_system'];

        return view('admin.category.index', compact('categories', 'rowData', 'headers', 'hiddenFields'));
    }

    public function create()
    {
        $this->authorize('create', Category::class);
        return view('admin.category.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Category::class);
        try {
            $category = $this->categoryService->createCategory($request->all());
            return redirect()->route('web.categories.index')
                ->with('success', 'Categoría creada exitosamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        }
    }

    public function edit($id)
    {
        $category = $this->categoryService->getCategoryById($id);
        $this->authorize('update', $category);

        return view('admin.category.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $category = $this->categoryService->getCategoryById($id);
        $this->authorize('update', $category);
        try {
            $this->categoryService->updateCategory($id, $request->all());
            return redirect()->route('web.categories.index')
                ->with('success', 'Categoría actualizada exitosamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $category = $this->categoryService->getCategoryById($id);
        $this->authorize('delete', $category);
        try {
            $this->categoryService->deleteCategory($id);
            return redirect()->route('web.categories.index')
                ->with('success', 'Categoría eliminada exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
