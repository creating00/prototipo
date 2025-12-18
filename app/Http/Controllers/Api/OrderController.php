<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseOrderController;
use Illuminate\Http\Request;
use App\Enums\OrderSource;

class OrderController extends BaseOrderController
{
    public function index()
    {
        // Solo usuarios autenticados pueden ver todas las órdenes
        if (!auth()->check()) {
            return response()->json([
                'error' => 'Authentication required to view orders'
            ], 401);
        }

        return response()->json(
            $this->orderService->getAllOrders()
        );
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            // Validar que solo usuarios autenticados puedan crear órdenes Backoffice
            if (!auth()->check()) {
                return response()->json([
                    'error' => 'Authentication required for Backoffice orders'
                ], 401);
            }

            // Forzar source Backoffice para usuarios autenticados
            $data['source'] = OrderSource::Backoffice->value;
            $data['user_id'] = auth()->id();

            $order = $this->orderService->createOrder($data);
            return response()->json($order, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function storeFromEcommerce(Request $request)
    {
        try {
            $data = $request->all();

            // Forzar el source a Ecommerce antes de pasar a OrderService
            $data['source'] = OrderSource::Ecommerce->value;

            // Crear la orden usando OrderService
            $order = $this->orderService->createOrder($data);

            // Solo retornar los campos que te interesan
            $response = [
                'id' => $order->id,
                'branch_id' => $order->branch_id,
                'customer_id' => $order->customer_id,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'notes' => $order->notes,
                'created_at' => $order->created_at,
            ];

            return response()->json($response, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    public function show($id)
    {
        // Solo usuarios autenticados pueden ver órdenes específicas
        if (!auth()->check()) {
            return response()->json([
                'error' => 'Authentication required to view order details'
            ], 401);
        }

        return response()->json(
            $this->orderService->getOrderById($id)
        );
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();

            // Prevenir cambios de source en actualizaciones
            if (isset($data['source'])) {
                unset($data['source']);
            }

            // Solo usuarios autenticados pueden actualizar órdenes
            if (!auth()->check()) {
                return response()->json([
                    'error' => 'Authentication required to update orders'
                ], 401);
            }

            $order = $this->orderService->updateOrder($id, $data);
            return response()->json($order);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function destroy($id)
    {
        // Solo usuarios autenticados pueden eliminar órdenes
        if (!auth()->check()) {
            return response()->json([
                'error' => 'Authentication required to delete orders'
            ], 401);
        }

        try {
            return response()->json(
                $this->orderService->deleteOrder($id)
            );
        } catch (\Exception $e) {
            return response()->json(
                ['error' => $e->getMessage()],
                $e->getCode() ?: 400
            );
        }
    }
}
