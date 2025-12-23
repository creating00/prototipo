<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseOrderController;
use Illuminate\Http\Request;
use App\Enums\OrderSource;
use App\Models\Client;

class OrderController extends BaseOrderController
{
    public function index()
    {
        return response()->json(
            $this->orderService->getAllOrders()
        );
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            // Forzar valores Backoffice
            $data['source'] = OrderSource::Backoffice->value;
            $data['user_id'] = config('orders.system_user_id');
            $data['customer_type'] = \App\Models\Branch::class;

            $order = $this->orderService->createOrder($data);

            return response()->json($order, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function storeFromEcommerce(Request $request)
    {
        try {
            $data = $request->all();

            $data['source'] = OrderSource::Ecommerce->value;

            // Validar customer_type explícito
            if (!isset($data['customer_type'])) {
                return response()->json([
                    'error' => 'customer_type is required'
                ], 422);
            }

            // Forzar reglas según customer_type
            if ($data['customer_type'] === Client::class) {
                if (!isset($data['client_id'])) {
                    return response()->json([
                        'error' => 'client_id is required for client orders'
                    ], 422);
                }
            }

            if ($data['customer_type'] === \App\Models\Branch::class) {
                if (!isset($data['branch_recipient_id'])) {
                    return response()->json([
                        'error' => 'branch_recipient_id is required for branch orders'
                    ], 422);
                }
            }

            $order = $this->orderService->createOrder($data);

            return response()->json([
                'id' => $order->id,
                'total_amount' => $order->total_amount,
                'status' => $order->status,
                'created_at' => $order->created_at,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function show($id)
    {
        return response()->json(
            $this->orderService->getOrderById($id)
        );
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();

            // Nunca permitir cambiar el source
            unset($data['source']);

            $order = $this->orderService->updateOrder($id, $data);

            return response()->json($order);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function destroy($id)
    {
        try {
            return response()->json(
                $this->orderService->deleteOrder($id)
            );
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }
}
