<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Sale;
use App\Services\Order\CartTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CartTransferController extends Controller
{
    public function __invoke(Request $request, CartTransferService $service): JsonResponse
    {
        Gate::authorize('createClient', Sale::class);
        Gate::authorize('createBranch', Order::class);

        $data = $request->validate([
            'branch_id' => ['required', 'integer'],
            'destination_branch_id' => ['required', 'integer'],
            'transfer_token' => ['required', 'uuid'],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $order = $service->send($data);

        return response()->json([
            'order_number' => $order->order_number,
            'redirect_url' => route('web.sales.create-client'),
            'orders_url' => route('web.orders.purchases'),
        ]);
    }
}
