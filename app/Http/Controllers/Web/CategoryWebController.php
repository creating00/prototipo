<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\BaseCategoryController;
use Illuminate\Http\Request;

class CategoryWebController extends BaseCategoryController
{
    public function index()
    {
        $rowData = $this->categoryService->getAllCategoriesForDataTable();
        $categories = $this->categoryService->getAllCategories();

        $headers = ['#', 'Nombre', 'Creado en:'];
        $hiddenFields = ['id', 'is_system'];

        return view('admin.category.index', compact('categories', 'rowData', 'headers', 'hiddenFields'));
    }

    public function create()
    {
        return view('admin.category.create');
    }

    public function store(Request $request)
    {
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
        return view('admin.category.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        try {
            $category = $this->categoryService->updateCategory($id, $request->all());
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
