<?php

namespace App\Http\Controllers;

use App\Services\CategoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class BaseCategoryController extends Controller
{
    protected CategoryService $categoryService;
    use AuthorizesRequests;
    
    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }
}
