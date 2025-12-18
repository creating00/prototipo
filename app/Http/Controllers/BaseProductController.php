<?php

namespace App\Http\Controllers;

use App\Services\ProductService;

abstract class BaseProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }
}
