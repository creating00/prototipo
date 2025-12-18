<?php

namespace App\Http\Controllers;

use App\Services\OrderService;

abstract class BaseOrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
}
