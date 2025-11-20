<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Client;
use App\Models\ClientAccount;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected $stockService;

    public function __construct(ProductStockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Crea o actualiza una orden
     */
    public function createOrUpdateOrder(array $validated, ?Order $order = null): Order
    {
        // Si es actualización, liberar stock y eliminar items anteriores
        if ($order) {
            $this->releaseOrderStock($order);
            $order->items()->delete();
        }

        // Obtener el cliente (ya sea del token o crear uno nuevo)
        $client = $this->getClientFromRequest($validated);

        // Determinar user_id basado en el tipo de autenticación
        $userId = $this->getUserIdFromRequest($validated);

        // Crear o actualizar la orden
        $orderData = [
            'client_id' => $client->id,
            'user_id' => $userId,
            'total_amount' => 0
        ];

        if ($order) {
            // Actualizar orden existente
            $order->update($orderData);
            // $order mantiene la referencia al modelo
        } else {
            // Crear nueva orden
            $order = Order::create($orderData);
        }

        // Procesar productos y calcular total
        $totalAmount = $this->processOrderProducts($order, $validated['productos']);

        // Actualizar el total de la orden
        $order->update(['total_amount' => $totalAmount]);

        return $order->load(['client', 'user', 'items.product']);
    }

    /**
     * Libera el stock de todos los productos de una orden
     */
    public function releaseOrderStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $product = Product::where('id', $item->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->stockService->release($product, $item->quantity);
        }
    }

    /**
     * Obtiene el cliente del token o crea uno nuevo
     */
    private function getClientFromRequest(array $validated): Client
    {
        // Si viene con token, usar el cliente autenticado
        if (isset($validated['token'])) {
            $clientAccount = $this->getClientAccountFromToken($validated['token']);

            if (!$clientAccount) {
                abort(401, 'Token inválido');
            }

            return $clientAccount->client;
        }

        // Si no hay token, crear o buscar cliente con los datos proporcionados
        return $this->findOrCreateClient($validated['cliente']);
    }

    /**
     * Obtiene la ClientAccount desde el token
     */
    private function getClientAccountFromToken(string $token): ?ClientAccount
    {
        return ClientAccount::whereHas('tokens', function ($query) use ($token) {
            $query->where('token', hash('sha256', explode('|', $token)[1] ?? ''));
        })->first();
    }

    /**
     * Crea o busca un cliente (para casos sin token)
     */
    private function findOrCreateClient(array $clienteData): Client
    {
        return Client::firstOrCreate(
            ['document' => $clienteData['document']],
            [
                'full_name' => $clienteData['full_name'],
                'phone' => $clienteData['phone'] ?? null,
                'address' => $clienteData['address'] ?? null
            ]
        );
    }

    /**
     * Procesa los productos de una orden y devuelve el total
     */
    private function processOrderProducts(Order $order, array $productos): float
    {
        $totalAmount = 0;

        foreach ($productos as $productoData) {
            $product = Product::where('id', $productoData['id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Reservar stock
            $this->stockService->reserve($product, $productoData['cantidad']);

            // Calcular subtotal
            $subtotal = $product->sale_price * $productoData['cantidad'];
            $totalAmount += $subtotal;

            // Crear order item
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $productoData['cantidad'],
                'unit_price' => $product->sale_price,
                'subtotal' => $subtotal
            ]);
        }

        return $totalAmount;
    }

    /**
     * Determina el user_id basado en el tipo de autenticación
     */
    private function getUserIdFromRequest(array $validated): ?int
    {
        // Token de cliente (e-commerce)
        if (isset($validated['token'])) {
            return null;
        }

        // ID de usuario del sistema
        if (isset($validated['id'])) {
            return $validated['id'];
        }

        // Guest (sin autenticación)
        return null;
    }
}
