<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\OrderValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    protected $orderService;
    protected $validationService;

    public function __construct(OrderService $orderService, OrderValidationService $validationService)
    {
        $this->orderService = $orderService;
        $this->validationService = $validationService;
    }

    public function index()
    {
        return Order::with(['client', 'user', 'items.product'])->get();
    }

    public function store(Request $request)
    {
        $validated = $this->validationService->validateOrderRequest($request);

        return DB::transaction(function () use ($validated) {
            return $this->orderService->createOrUpdateOrder($validated);
        });
    }

    public function show(Order $order)
    {
        return $order->load(['client', 'user', 'items.product']);
    }

    public function update(Request $request, Order $order)
    {
        $validated = $this->validationService->validateOrderRequest($request);

        return DB::transaction(function () use ($validated, $order) {
            return $this->orderService->createOrUpdateOrder($validated, $order);
        });
    }

    public function destroy(Order $order)
    {
        return DB::transaction(function () use ($order) {
            $this->orderService->releaseOrderStock($order);
            $order->delete();

            return response()->json(['message' => 'Order deleted']);
        });
    }
}
