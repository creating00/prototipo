<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseOrderController;
use Illuminate\Http\Request;
use App\Enums\OrderSource;
use App\Models\Client;
use App\Traits\AuthTrait;

class OrderController extends BaseOrderController
{
    use AuthTrait;

    protected $clientService;

    public function __construct(
        \App\Services\OrderService $orderService,
        \App\Services\ClientService $clientService
    ) {
        parent::__construct($orderService);
        $this->clientService = $clientService;
    }

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

            $data['items'] = array_map(function ($item) {
                return array_merge([
                    'currency' => \App\Enums\CurrencyType::ARS->value,
                ], $item);
            }, $data['items']);

            if (!isset($data['customer_type'])) {
                return response()->json(['error' => 'customer_type is required'], 422);
            }

            // Validación lógica para Clientes
            if ($data['customer_type'] === Client::class) {
                // Si no hay ID y tampoco hay datos de cliente nuevo, error
                if (!isset($data['client_id']) && !isset($data['client'])) {
                    return response()->json([
                        'error' => 'client_id or client data is required for client orders'
                    ], 422);
                }

                // Si es invitado (viene 'client'), lo creamos o buscamos antes de pasar al service de órdenes
                if (!isset($data['client_id']) && isset($data['client'])) {
                    $client = $this->clientService->findOrCreate($data['client']);
                    $data['client_id'] = $client->id;
                }
            }

            // Validación para Sucursales (Transferencias)
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
                'totals' => $order->totals,
                'formatted_totals' => $order->formatted_totals,
                'status' => $order->status,
                'created_at' => $order->created_at,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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

    /**
     * Convierte una orden existente en una venta.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function convert(Request $request, $id)
    {
        try {
            // Recolectamos las opciones del request
            $options = $request->only([
                'payment_type',
                'amount_received',
                'user_id'
            ]);

            /**
             * Prioridad del Usuario:
             * 1. El que viene explícitamente en el JSON del Request.
             * 2. El usuario autenticado (si funcionara).
             * 3. El usuario de sistema definido en config/orders.php.
             */
            if (!isset($options['user_id'])) {
                $options['user_id'] = $this->userId() ?? config('orders.system_user_id');
            }

            // Ejecutar conversión
            $sale = $this->orderService->convertToSale($id, $options);

            return response()->json([
                'message' => 'Orden convertida a venta exitosamente',
                'sale_id' => $sale->id,
                'internal_number' => $sale->internal_number,
                'sale' => $sale
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'La orden no existe'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
