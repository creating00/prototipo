<?php

namespace App\Services;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Branch;
use App\Models\Client;
use App\Models\ClientAccount;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
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

    public function buildOrderItemsHtml(Order $order): array
    {
        return $order->items->map(function ($item) use ($order) {
            return [
                'html' => view('admin.order.partials._item_row', [
                    'product'   => $item->product,
                    'item'      => $item,
                    'stock'     => $item->product->getStock($order->branch_id),
                    'salePrice' => $item->unit_price,
                ])->render(),
            ];
        })->values()->toArray();
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

    /**
     * Convierte una orden en venta permitiendo personalizar el pago y el usuario.
     *
     * @param int $id ID de la orden
     * @param array $options Datos opcionales (payment_type, user_id, amount_received)
     * @return Sale
     */
    public function convertToSale($id, array $options = [])
    {
        return DB::transaction(function () use ($id, $options) {
            $order = $this->getOrderById($id);

            if ($order->status === OrderStatus::ConvertedToSale->value) {
                throw new \Exception("Esta orden ya fue convertida a venta.");
            }

            // Determinamos el tipo de pago por defecto si no se envía uno
            $defaultPaymentType = ($order->customer_type === Branch::class)
                ? \App\Enums\PaymentType::Transfer->value
                : \App\Enums\PaymentType::Cash->value;

            $data = [
                'source_order_id' => $order->id,
                'branch_id'       => $order->branch_id,
                'user_id'         => $options['user_id'] ?? ($this->userId() ?? $order->user_id),
                'customer_type'   => $order->customer_type,
                'client_id'           => ($order->customer_type === Client::class) ? $order->customer_id : null,
                'branch_recipient_id' => ($order->customer_type === Branch::class) ? $order->customer_id : null,

                'sale_type' => \App\Enums\SaleType::Sale->value,
                'sale_date' => now()->format('Y-m-d'),
                'status'    => \App\Enums\SaleStatus::Paid->value,

                'items'     => $order->items->map(fn($i) => [
                    'product_id' => $i->product_id,
                    'quantity'   => $i->quantity,
                    'unit_price' => $i->unit_price,
                ])->toArray(),

                // 2. Prioridad al payment_type enviado
                'payment_type'    => $options['payment_type'] ?? $defaultPaymentType,

                // 3. Monto recibido (por defecto el total de la orden)
                'amount_received' => $options['amount_received'] ?? $order->total_amount,
            ];

            $sale = app(SaleService::class)->createSale($data);

            $order->update([
                'status' => OrderStatus::ConvertedToSale->value,
                'sale_id' => $sale->id
            ]);

            return $sale;
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

    /**
     * Prepara los datos de los items del pedido para la DataTable en la vista de detalle.
     */
    public function getOrderItemsData(Order $order): array
    {
        $headers = ['#', 'Producto', 'Cantidad', 'Precio Unitario', 'Subtotal'];

        $rowData = $order->items->map(function ($item, $index) {
            return [
                'id'         => $item->id,
                'number'     => $index + 1, // <--- Cambiado de 'index' a 'number'
                'product'    => $item->product->name,
                'quantity'   => $item->quantity,
                // Usamos formatCurrency si quieres mantener el estilo con colores
                'unit_price' => $this->formatCurrency($item->unit_price, '$', 'text-dark'),
                'subtotal'   => $this->formatCurrency($item->subtotal, '$', 'fw-bold text-dark'),
            ];
        })->toArray();

        return [
            'headers'      => $headers,
            'rowData'      => $rowData,
            'hiddenFields' => ['id']
        ];
    }

    /**
     * Obtiene las órdenes donde la sucursal actual es el cliente (Compras Internas).
     */
    public function getPurchasedOrders()
    {
        $branchId = $this->currentBranchId();

        return Order::with(['branch', 'customer', 'user'])
            ->where('customer_id', $branchId)
            ->where('customer_type', \App\Models\Branch::class)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Formatea las compras para la DataTable.
     */
    public function getPurchasedOrdersForDataTable(): array
    {
        return $this->getPurchasedOrders()->map(function ($order, $index) {
            // Usamos el formatter del trait
            $data = $this->formatForDataTable($order, $index, [
                'currencyClass' => 'fw-bold text-info' // Azul para diferenciarlo de ventas (verde)
            ]);

            // Ajuste: En esta vista, 'branch' es el PROVEEDOR (quien nos vende)
            return $data;
        })->toArray();
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
