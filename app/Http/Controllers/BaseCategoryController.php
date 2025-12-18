<?php

namespace App\Http\Controllers;

use App\Services\CategoryService;

abstract class BaseCategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }
}
