<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\ProductStockService;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    protected $stockService;

    public function __construct(ProductStockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index()
    {
        return Order::with(['product', 'client', 'user'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id'       => 'required|exists:products,id',
            'client_id'        => 'required|exists:clients,id',
            'user_id'          => 'required|exists:users,id',
            'quantity'         => 'required|integer|min:1',
            'amount_to_charge' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {

            $product = Product::where('id', $validated['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $this->stockService->reserve($product, $validated['quantity']);

            return Order::create($validated);
        });
    }

    public function show(Order $order)
    {
        return $order->load(['product', 'client', 'user']);
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'product_id'       => 'required|exists:products,id',
            'client_id'        => 'required|exists:clients,id',
            'user_id'          => 'required|exists:users,id',
            'quantity'         => 'required|integer|min:1',
            'amount_to_charge' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $order) {

            $oldProduct = Product::where('id', $order->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->stockService->release($oldProduct, $order->quantity);

            $newProduct = Product::where('id', $validated['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $this->stockService->reserve($newProduct, $validated['quantity']);

            $order->update($validated);

            return $order->load(['product', 'client', 'user']);
        });
    }

    public function destroy(Order $order)
    {
        return DB::transaction(function () use ($order) {

            $product = Product::where('id', $order->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->stockService->release($product, $order->quantity);

            $order->delete();

            return response()->json(['message' => 'Order deleted']);
        });
    }
}
