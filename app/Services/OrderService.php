<?php

namespace App\Services;

use App\Enums\OrderSource;
use App\Models\Client;
use App\Models\ClientAccount;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Order\OrderDataProcessor;
use App\Services\Order\OrderItemProcessor;
use App\Services\Product\ProductStockService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Services\Traits\DataTableFormatter;
use Illuminate\Support\Str;

class OrderService
{
    use DataTableFormatter;

    protected ProductStockService $stockService;
    protected OrderDataProcessor $dataProcessor;
    protected OrderItemProcessor $itemProcessor;
    protected ClientService $clientService;

    public function __construct(
        ProductStockService $stockService,
        OrderDataProcessor $dataProcessor = null,
        OrderItemProcessor $itemProcessor = null,
        ClientService $clientService = null
    ) {
        $this->stockService = $stockService;
        $this->dataProcessor = $dataProcessor ?? new OrderDataProcessor();
        $this->itemProcessor = $itemProcessor ?? new OrderItemProcessor($stockService);
        $this->clientService = $clientService ?? new ClientService();
    }

    public function getAllOrders()
    {
        return Order::with(['branch', 'customer', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getOrderById($id): Order
    {
        return Order::with(['items.product', 'payments', 'customer'])
            ->findOrFail($id);
    }

    public function createOrder(array $data): Order
    {
        $validated = $this->validateOrderData($data);

        return DB::transaction(function () use ($validated) {
            $orderData = $this->dataProcessor->prepare($validated);
            $order = $this->createOrderRecord($orderData);
            $total = $this->itemProcessor->sync($order, $orderData['items']);

            $order->update(['total_amount' => $total]);
            return $order->fresh();
        });
    }

    public function updateOrder($id, array $data): Order
    {
        $order = $this->getOrderById($id);
        $validated = $this->validateOrderData($data, $order->id);

        return DB::transaction(function () use ($order, $validated) {
            $this->itemProcessor->releaseStock($order);
            $order->items()->delete();

            $orderData = $this->dataProcessor->prepare($validated, $order);
            $this->updateOrderRecord($order, $orderData);

            $total = $this->itemProcessor->sync($order, $orderData['items']);
            $order->update(['total_amount' => $total]);

            return $order->fresh();
        });
    }

    public function deleteOrder($id): array
    {
        $order = $this->getOrderById($id);

        if ($order->payments()->count() > 0) {
            throw new \Exception('Cannot delete an order that has payments associated', 400);
        }

        return DB::transaction(function () use ($order) {
            $this->itemProcessor->releaseStock($order);
            $order->items()->delete();
            $order->delete();

            return ['message' => 'Order deleted'];
        });
    }

    public function validateOrderData(array $data, $ignoreId = null): array
    {
        $validator = Validator::make($data, $this->getValidationRules($data));

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public function getAllOrdersForDataTable(): array
    {
        return $this->getAllOrders()->map(
            fn($order, $index) => $this->formatForDataTable($order, $index)
        )->toArray();
    }

    protected function getValidationRules(array $data): array
    {
        $rules = [
            'branch_id' => 'nullable|exists:branches,id',
            'status' => 'required|integer',
            'source' => 'required|integer|in:1,2',
            'sale_id' => 'nullable|exists:sales,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ];

        if (($data['source'] ?? null) == OrderSource::Ecommerce->value) {
            // Permitir token opcional
            $rules['token'] = 'nullable|string';

            // Permitir client opcional, validar solo si se envía
            $rules['client'] = 'nullable|array';
            $rules['client.document'] = 'required_with:client|string';
            $rules['client.full_name'] = 'required_with:client|string';
        } else {
            $rules['user_id'] = 'required|exists:users,id';
            $rules['customer_type'] = 'required|in:App\Models\Client,App\Models\Branch';

            if (($data['customer_type'] ?? null) === Client::class) {
                $rules['client_id'] = 'required|exists:clients,id';
            } elseif (($data['customer_type'] ?? null) === \App\Models\Branch::class) {
                $rules['branch_recipient_id'] = 'required|exists:branches,id';
            }
        }

        return $rules;
    }

    protected function createOrderRecord(array $orderData): Order
    {
        return Order::create([
            'branch_id' => $orderData['branch_id'],
            'user_id' => $orderData['user_id'],
            'status' => $orderData['status'],
            'source' => $orderData['source'],
            'sale_id' => $orderData['sale_id'] ?? null,
            'notes' => $orderData['notes'] ?? null,
            'total_amount' => 0,
            'customer_id' => $orderData['customer_id'],
            'customer_type' => $orderData['customer_type'],
        ]);
    }

    protected function updateOrderRecord(Order $order, array $orderData): void
    {
        $order->update([
            'branch_id' => $orderData['branch_id'],
            'user_id' => $orderData['user_id'],
            'status' => $orderData['status'],
            'source' => $orderData['source'],
            'sale_id' => $orderData['sale_id'] ?? null,
            'notes' => $orderData['notes'] ?? null,
            'customer_id' => $orderData['customer_id'],
            'customer_type' => $orderData['customer_type'],
        ]);
    }
}
