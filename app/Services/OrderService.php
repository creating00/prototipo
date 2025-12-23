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
use App\Traits\AuthTrait;
use Illuminate\Support\Str;

class OrderService
{
    use DataTableFormatter, AuthTrait;

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

    // public function getAllOrders()
    // {
    //     $branchId = $this->currentBranchId();
    //     return Order::with(['branch', 'customer', 'user'])
    //         ->orderBy('created_at', 'desc')
    //         ->get();
    // }

    public function getAllOrders()
    {
        $branchId = $this->currentBranchId();

        $query = Order::with(['branch', 'customer', 'user'])
            ->orderBy('created_at', 'desc');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get();
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
            'customer_type' => 'required|in:App\Models\Client,App\Models\Branch',
        ];

        /**
         * Reglas específicas por tipo de customer
         */
        if (($data['customer_type'] ?? null) === Client::class) {

            // Ecommerce puede enviar client embebido
            if (($data['source'] ?? null) == OrderSource::Ecommerce->value) {
                $rules['client'] = 'required|array';
                $rules['client.document'] = 'required|string';
                $rules['client.full_name'] = 'required|string';
            } else {
                // Backoffice
                $rules['client_id'] = 'required|exists:clients,id';
            }
        } elseif (($data['customer_type'] ?? null) === \App\Models\Branch::class) {

            $rules['branch_recipient_id'] = 'required|exists:branches,id';
        }

        /**
         * Reglas específicas por source
         */
        if (($data['source'] ?? null) == OrderSource::Backoffice->value) {
            $rules['user_id'] = 'required|exists:users,id';
        }

        if (($data['source'] ?? null) == OrderSource::Ecommerce->value) {
            $rules['token'] = 'nullable|string';
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
