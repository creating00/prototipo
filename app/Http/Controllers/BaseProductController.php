<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class BaseProductController extends Controller
{
    protected ProductService $productService;
    use AuthorizesRequests;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }
}
