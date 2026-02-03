<?php

namespace App\Services;

use App\Enums\CurrencyType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderReception;
use App\Models\Sale;
use App\Services\Order\OrderDataProcessor;
use App\Services\Order\OrderItemProcessor;
use App\Services\Product\ProductStockService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Services\Traits\DataTableFormatter;
use App\Traits\AuthTrait;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
            $query->forBranch($branchId);
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
            $totals = $this->itemProcessor->sync($order, $orderData['items']);

            $order->update([
                'totals' => $totals
            ]);

            return $order->fresh();
        });
    }

    public function updateOrder($id, array $data): Order
    {
        $order = $this->getOrderById($id);
        $validated = $this->validateOrderData($data, $order->id);

        return DB::transaction(function () use ($order, $validated) {
            $orderData = $this->dataProcessor->prepare($validated, $order);

            // 1. Liberar stock actual antes de procesar cambios
            $this->itemProcessor->releaseStock($order);

            // 2. Sincronizar (el sync ya maneja crear, editar y eliminar)
            // Pasamos los items y el sync se encarga de borrar los que no vienen en $orderData['items']
            $totals = $this->itemProcessor->sync($order, $orderData['items']);

            // 3. Actualizar datos de la orden
            $this->updateOrderRecord($order, $orderData);

            $order->update([
                'totals' => $totals
            ]);

            return $order->fresh(['items']);
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
            $order->load('items');

            if ($order->status === OrderStatus::ConvertedToSale->value) {
                throw new \Exception("Esta orden ya fue convertida a venta.");
            }

            // 1. Usar la tasa que envió el usuario (evita discrepancias por centavos)
            $rate = (float)($options['exchange_rate_blue'] ?? app(CurrencyExchangeService::class)->getCurrentDollarRate());

            $arsKey = \App\Enums\CurrencyType::ARS->value;
            $usdKey = \App\Enums\CurrencyType::USD->value;

            // 2. Determinar qué moneda se está pagando y el monto consolidado
            // Si total_amount_usd tiene valor, el pago es en dólares.
            $isPayingInUsd = !empty($options['total_amount_usd']);
            $totalConsolidado = $isPayingInUsd
                ? (float)$options['total_amount_usd']
                : (float)($options['total_amount'] ?? 0);

            $currencyId = $isPayingInUsd ? $usdKey : $arsKey;

            $userId = $options['user_id'] ?? $this->userId();
            if (!$userId) throw new \Exception('No se pudo determinar el usuario.');

            // 3. Mapear ítems (Forzamos ARS para la tabla de ventas)
            $items = $order->items->map(function ($i) {
                return [
                    'product_id' => $i->product_id,
                    'quantity'   => $i->quantity,
                    'unit_price' => $i->unit_price,
                    'currency'   => is_object($i->currency) ? $i->currency->value : $i->currency,
                ];
            })->values()->toArray();

            if (empty($items)) {
                throw new \Exception("Error: La orden no tiene ítems cargados.");
            }

            // 4. Estructurar data para SaleService
            $data = [
                'source_order_id'     => $order->id,
                'branch_id'           => $order->branch_id,
                'user_id'             => $userId,
                'customer_type'       => $order->customer_type,
                'sale_type'           => \App\Enums\SaleType::Sale->value,
                'sale_date'           => now()->format('Y-m-d'),
                'status'              => \App\Enums\SaleStatus::Paid->value,
                'items'               => $items,
                'payment_type'        => $options['payment_type_1'] ?? $options['payment_type'] ?? \App\Enums\PaymentType::Cash->value,
                'amount_received'     => (float)($options['amount_received'] ?? $totalConsolidado),
                'currency_id'         => $currencyId, // Informamos al servicio en qué moneda paga
                'exchange_rate'       => $rate,
                'skip_stock_movement' => true,
                'totals' => json_encode([
                    $currencyId => $totalConsolidado,
                ]),
            ];

            // Mapeo cliente/sucursal
            if ($order->customer_type === Client::class) {
                $data['client_id'] = $order->customer_id;
            } else {
                $data['branch_recipient_id'] = $order->customer_id;
            }

            //dd($data['items']);

            // 5. Crear Venta y actualizar Orden
            $sale = app(SaleService::class)->createSale($data);

            $order->update([
                'status'  => OrderStatus::ConvertedToSale->value,
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
                'number'     => $index + 1,
                'product'    => $item->product->name,
                'quantity'   => $item->quantity,
                'unit_price' => $this->formatCurrency(
                    $item->unit_price,
                    $item->currency,
                    'text-dark'
                ),
                'subtotal'   => $this->formatCurrency(
                    $item->subtotal,
                    $item->currency,
                    'fw-bold text-dark'
                ),
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
            $reception = $order->reception;
            $statusSource = $reception ?? $order;

            return [
                'id'            => $order->id,
                'status_raw'    => is_object($statusSource->status) ? $statusSource->status->value : $statusSource->status,
                'is_received'   => $reception ? 'true' : 'false',
                'customer'      => $this->resolveCustomerName($order),
                'customer_type' => $order->customer_type,
                'phone'         => $this->cleanPhoneNumber($order->customer?->phone),
                'observation'   => $reception ? ($reception->observation ?? 'Sin notas') : '---',
                'number'        => $index + 1,                                  // #
                'branch'        => $order->branch->name ?? 'N/A',               // Proveedor
                'total' => collect($order->totals)
                    ->map(fn($v, $k) => $this->formatCurrency($v, CurrencyType::from($k)))
                    ->implode(' / '),
                'status'        => $this->resolveStatus($statusSource, ['currencyClass' => 'fw-bold text-info']), // Estado
                'created_at'    => $order->created_at->format('d-m-Y'),         // Fecha Solicitud
                'received_at'   => $reception ? $reception->received_at->format('d-m-Y H:i') : '---', // Fecha Recepción
                'received_by'   => $reception ? $reception->user->name : '---', // Recibido por
            ];
        })->toArray();
    }

    protected function getValidationRules(array $data): array
    {
        $source = $data['source'] ?? null;
        $customerType = $data['customer_type'] ?? null;

        $rules = [
            'branch_id' => 'nullable|exists:branches,id',
            'status' => 'required|integer',
            'source' => 'required|integer|in:1,2',
            'sale_id' => 'nullable|exists:sales,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.currency' => ['required', Rule::enum(CurrencyType::class)],
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'customer_type' => 'required|in:App\Models\Client,App\Models\Branch',
        ];

        // Validación por tipo de Cliente
        if ($customerType === Client::class) {
            if ($source == OrderSource::Ecommerce->value) {
                // Ecommerce puede enviar token o datos del cliente
                $rules['token'] = 'nullable|string';
                $rules['client'] = 'required_without:token|array';
                $rules['client.document'] = 'required_with:client|string';
                $rules['client.full_name'] = 'required_with:client|string';
            } else {
                $rules['client_id'] = 'required|exists:clients,id';
            }
        }
        // Validación por tipo Sucursal
        elseif ($customerType === \App\Models\Branch::class) {
            $rules['branch_recipient_id'] = 'required_without:customer_id|exists:branches,id';
            $rules['customer_id'] = 'required_without:branch_recipient_id|exists:branches,id';
        }

        // El user_id solo es obligatorio si viene del Backoffice
        if ($source == OrderSource::Backoffice->value) {
            $rules['user_id'] = 'required|exists:users,id';
        }

        // Exchange rate: obligatorio solo al crear
        if (!isset($data['id'])) {
            $rules['exchange_rate'] = [
                'required',
                'numeric',
                'min:1',
            ];
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
            'exchange_rate' => $orderData['exchange_rate'],
            'totals' => [],
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

    /**
     * Registra la recepción física del pedido por parte de la sucursal solicitante.
     */
    public function registerReception(int $orderId, array $data): OrderReception
    {
        return DB::transaction(function () use ($orderId, $data) {
            // 1. Cargar el pedido con sus ítems y productos
            $order = Order::with('items.product')->findOrFail($orderId);

            // 2. Validaciones de integridad
            if ($order->customer_type !== \App\Models\Branch::class) {
                throw new \Exception("Solo los pedidos entre sucursales requieren registro de recepción.");
            }

            if ($order->reception()->exists()) {
                throw new \Exception("Este pedido ya cuenta con un registro de recepción.");
            }

            // 3. Determinar el estado basado en la observación
            // Si hay texto en 'observation', usamos ReceivedWithIssues, de lo contrario Received
            $status = !empty($data['observation'])
                ? \App\Enums\OrderReceptionStatus::ReceivedWithIssues
                : \App\Enums\OrderReceptionStatus::Received;

            // 4. Crear el registro de recepción
            $reception = $order->reception()->create([
                'user_id'     => $this->userId() ?? $data['user_id'],
                'status'      => $status,
                'received_at' => now(),
                'observation' => $data['observation'] ?? null,
            ]);

            // 5. Aumentar stock en la sucursal que recibe (customer_id)
            foreach ($order->items as $item) {
                // Bloqueamos para evitar condiciones de carrera
                $product = \App\Models\Product::where('id', $item->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->stockService->addStock($product, $item->quantity, $order->customer_id);

                $this->stockService->updatePurchasePrice(
                    $product,
                    $order->customer_id,
                    (float)$item->unit_price,
                    is_object($item->currency) ? $item->currency->value : $item->currency
                );
            }

            // 6. Opcional: Actualizar el estado del Pedido a 'Completado'
            // $order->update(['status' => \App\Enums\OrderStatus::Completed]);

            return $reception;
        });
    }
}
