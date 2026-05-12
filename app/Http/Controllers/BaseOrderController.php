<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class BaseOrderController extends Controller
{
    use AuthorizesRequests;
    
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
}
