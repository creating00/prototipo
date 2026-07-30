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
use App\Models\User;
use App\Services\Order\OrderDataProcessor;
use App\Services\Order\OrderItemProcessor;
use App\Services\Product\ProductStockService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Notifications\OrderStatusNotification;
use Illuminate\Support\Facades\Notification;
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

    public function getAllOrders($user = null)
    {
        $branchId = $this->currentBranchId();

        $query = Order::with(['branch', 'customer', 'user'])
            ->where('source', '!=', OrderSource::Manual)
            ->orderBy('created_at', 'desc');

        // Filtro por sucursal proveedora / vendedora
        if ($branchId) {
            $query->where('branch_id', $branchId)
                ->where(function ($q) use ($branchId) {
                    // Excluir compras / autopedidos donde la sucursal actual es la compradora
                    $q->where('customer_type', '!=', \App\Models\Branch::class)
                      ->orWhereNull('customer_type')
                      ->orWhere('customer_id', '!=', $branchId);
                });
        }

        // Lógica de Rol: Si es Seller, solo ve pedidos de Clientes
        if ($user && $user->hasRole('seller')) {
            $query->forClientsOnly();
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
                    'stock'     => $item->product ? $item->product->getStock($order->branch_id) : 0,
                    'salePrice' => $item->unit_price,
                ])->render(),
            ];
        })->values()->toArray();
    }

    public function createOrder(array $data): Order
    {
        // Forzar estado del pedido por defecto a Pendiente
        if (!isset($data['status'])) {
            $data['status'] = OrderStatus::Pending->value;
        }

        // El estado de pago siempre inicia en Pendiente (2)
        $data['payment_status'] = 2;

        $validated = $this->validateOrderData($data);

        return DB::transaction(function () use ($validated, $data) {
            $orderData = $this->dataProcessor->prepare($validated);
            $orderData['payment_status'] = 2;
            if (isset($data['created_at']) && !empty($data['created_at'])) {
                $orderData['created_at'] = $data['created_at'];
            }
            $order = $this->createOrderRecord($orderData);

            // El registro inicial NO descuenta stock (skipStockMovement = true)
            $totals = $this->itemProcessor->sync($order, $orderData['items'], true);

            $order->update([
                'totals' => $totals
            ]);

            $order = $order->fresh(['items', 'customer']);

            $itemsCount = $order->items->sum('quantity');
            $customerName = $order->customer_name;

            $message = "{$customerName} hizo un pedido de {$itemsCount} productos.";

            $targetUsers = User::where('branch_id', $order->branch_id)->get();
            if ($targetUsers->isNotEmpty()) {
                Notification::send($targetUsers, new OrderStatusNotification($order, $message));
            }

            return $order->fresh();
        });
    }

    public function updateOrder($id, array $data): Order
    {
        $order = $this->getOrderById($id);

        if (!$order->canBeEdited()) {
            throw new \Exception('No se puede modificar un pedido que ya ha sido enviado al stock.');
        }

        $validated = $this->validateOrderData($data, $order->id);

        return DB::transaction(function () use ($order, $validated, $data) {
            $orderData = $this->dataProcessor->prepare($validated, $order);
            if (isset($data['created_at']) && !empty($data['created_at'])) {
                $orderData['created_at'] = $data['created_at'];
            }

            // Mantenemos sin descuento de stock durante las modificaciones antes de enviar al stock
            $totals = $this->itemProcessor->sync($order, $orderData['items'], true);

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
            // Omitir liberación de stock si la orden ya fue cancelada
            if ($order->status !== \App\Enums\OrderStatus::Cancelled) {
                $this->itemProcessor->releaseStock($order);
            }

            $order->items()->delete();
            $order->delete();

            return ['message' => 'Order deleted'];
        });
    }

    public function cancelOrder($id): array
    {
        $order = $this->getOrderById($id);

        // Evitar procesar si ya está cancelada
        if ($order->status === OrderStatus::Cancelled) {
            throw new \Exception('La orden ya se encuentra cancelada.', 400);
        }

        return DB::transaction(function () use ($order) {
            // 1. Revertir el stock
            $this->itemProcessor->releaseStock($order);

            // 2. Actualizar el estado usando el Enum
            $order->update([
                'status' => OrderStatus::Cancelled
            ]);

            return ['message' => 'Order cancelled successfully'];
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
        // dd($options); // Comentado para permitir el flujo

        return DB::transaction(function () use ($id, $options) {
            $order = $this->getOrderById($id);
            $order->load('items');

            if ($order->status === OrderStatus::ConvertedToSale->value) {
                throw new \Exception("Esta orden ya fue convertida a venta.");
            }

            // 1. Tasa y determinación de moneda
            $rate = (float)($options['exchange_rate_blue'] ?? app(CurrencyExchangeService::class)->getCurrentDollarRate());
            $arsKey = \App\Enums\CurrencyType::ARS->value;
            $usdKey = \App\Enums\CurrencyType::USD->value;

            $isPayingInUsd = !empty($options['total_amount_usd']);
            $totalConsolidado = $isPayingInUsd
                ? (float)$options['total_amount_usd']
                : (float)($options['total_amount'] ?? 0);

            $currencyId = $isPayingInUsd ? $usdKey : $arsKey;
            $userId = $options['user_id'] ?? $this->userId();

            if (!$userId) throw new \Exception('No se pudo determinar el usuario.');

            // 2. Mapeo de ítems
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

            // 3. Preparación de datos de venta y pagos
            $isDual = (bool)($options['is_dual_payment'] ?? false);

            $data = [
                'source_order_id'     => $order->id,
                'branch_id'           => $order->branch_id,
                'user_id'             => $userId,
                'customer_type'       => $order->customer_type,
                'sale_type'           => \App\Enums\SaleType::Sale->value,
                'sale_date'           => now()->format('Y-m-d'),
                'status'              => \App\Enums\SaleStatus::Paid->value,
                'items'               => $items,
                'currency_id'         => $currencyId,
                'exchange_rate_blue'  => $rate,
                'totals'              => json_encode([$currencyId => $totalConsolidado]),

                // Flags para HandlesSalePayments
                'enable_dual_payment' => $isDual ? 1 : 0,
                'requires_invoice'    => !empty($options['requires_invoice']),

                // Pago 1: Normalizado (Viene de _1 gracias a la sincronización JS)
                'payment_type'      => $options['payment_type_1'] ?? null,
                'amount_received'   => (float)($options['amount_received_1'] ?? 0),
                'payment_method_id' => $options['bank_account_id_1'] ?? $options['bank_id_1'] ?? null,
                'payment_notes'     => $options['payment_notes'] ?? null,
            ];

            // Pago 2: Solo si es dual
            if ($isDual) {
                $data['payment_type_2']      = $options['payment_type_2'] ?? null;
                $data['amount_received_2']   = (float)($options['amount_received_2'] ?? 0);
                $data['payment_method_id_2'] = $options['bank_account_id_2'] ?? $options['bank_id_2'] ?? null;
            }

            // 4. Mapeo cliente/sucursal
            if ($order->customer_type === \App\Models\Client::class) {
                $data['client_id'] = $order->customer_id;
            } else {
                $data['branch_recipient_id'] = $order->customer_id;
            }

            // 5. Ejecución
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

    public function getAllOrdersForDataTable($user = null): array
    {
        return $this->getAllOrders($user)->map(
            fn($order, $index) => $this->formatOrderForDataTable($order, $index)
        )->toArray();
    }

    /**
     * Prepara los datos de los items del pedido para la DataTable en la vista de detalle.
     */
    public function getOrderItemsData(Order $order): array
    {
        $headers = ['N°', 'Producto', 'Stock', 'Precio', 'Cantidad', 'Subtotal'];

        $rowData = $order->items->map(function ($item, $index) use ($order) {
            $stock = $item->product ? $item->product->getStock($order->branch_id) : 0;

            return [
                'id'         => $item->id,
                'number'     => $index + 1,
                'product'    => $item->product?->name ?? '<span class="text-muted fst-italic">Producto eliminado</span>',
                'stock'      => '<span class="badge bg-secondary">' . $stock . '</span>',
                'unit_price' => $this->formatCurrency(
                    $item->unit_price,
                    $item->currency,
                    'text-dark'
                ),
                'quantity'   => $item->quantity,
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

        $query = Order::with(['branch', 'customer', 'user', 'reception'])
            ->where(function ($q) {
                $q->where('customer_type', \App\Models\Branch::class)
                  ->orWhere('source', OrderSource::Manual);
            })
            ->orderBy('created_at', 'desc');

        if ($branchId) {
            $query->where('customer_id', $branchId);
        }

        return $query->get();
    }

    /**
     * Formatea las compras para la DataTable.
     */
    public function getPurchasedOrdersForDataTable(): array
    {
        return $this->getPurchasedOrders()->map(function ($order, $index) {
            $reception = $order->reception;
            $statusSource = $reception ?? $order;
            $isStockSent = (bool)$order->is_stock_sent || (bool)$reception;

            $sourceRaw   = is_object($order->source) ? $order->source->value : $order->source;
            $sourceLabel = is_object($order->source) ? $order->source->label() : $order->source;
            $sourceBadgeClass = match ((int)$sourceRaw) {
                \App\Enums\OrderSource::Ecommerce->value => 'bg-info',
                \App\Enums\OrderSource::Manual->value    => 'badge-purple',
                default                                  => 'bg-secondary',
            };

            return [
                'id'            => $order->id,
                'status_raw'    => is_object($statusSource->status) ? $statusSource->status->value : $statusSource->status,
                'source_raw'    => $sourceRaw,
                'is_received'   => $isStockSent ? 'true' : 'false',
                'customer'      => $this->resolveCustomerName($order),
                'customer_type' => $order->customer_type,
                'phone'         => $this->cleanPhoneNumber($order->customer?->phone),
                'observation'   => $reception ? ($reception->observation ?? 'Sin notas') : '---',
                'number'         => $index + 1,                                  // #
                'branch'         => $order->branch->name ?? 'N/A',               // Proveedor
                'total' => collect($order->totals)
                    ->map(fn($v, $k) => $this->formatCurrency($v, CurrencyType::from($k)))
                    ->implode(' / '),
                'source'         => '<span class="badge ' . $sourceBadgeClass . '">' . $sourceLabel . '</span>', // Canal
                'status'         => $this->resolveStatus($statusSource, ['currencyClass' => 'fw-bold text-info']), // Estado
                'payment_status' => sprintf('<span class="%s">%s</span>', $order->payment_status_badge_class, $order->payment_status_label), // Estado Pago
                'created_at'     => $order->created_at->format('d-m-Y'),         // Fecha Solicitud
                'received_at'    => $reception ? $reception->received_at->format('d-m-Y H:i') : ($order->stock_sent_at ? $order->stock_sent_at->format('d-m-Y H:i') : '---'),
                'received_by'    => $order->user->name ?? ($reception?->user?->name ?? '---'),
                '_row_attributes' => [
                    'id'            => $order->id,
                    'status_raw'    => is_object($statusSource->status) ? $statusSource->status->value : $statusSource->status,
                    'source_raw'    => $sourceRaw,
                    'is_received'   => $isStockSent ? 'true' : 'false',
                    'can_edit'      => ($order->canBeEdited() && !$isStockSent) ? 'true' : 'false',
                ]
            ];
        })->toArray();
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

            if ($order->is_stock_sent || $order->reception()->exists()) {
                throw new \Exception("Este pedido ya fue enviado al stock / recibido anteriormente.");
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

            // 6. Actualizar el estado del Pedido a enviado al stock y confirmado
            $order->update([
                'is_stock_sent' => true,
                'stock_sent_at' => now(),
                'status'        => OrderStatus::Confirmed,
            ]);

            return $reception;
        });
    }

    /**
     * Incrementa el stock de la sucursal según el pedido y bloquea la orden para modificaciones.
     */
    public function sendToStock(int $id): Order
    {
        $order = $this->getOrderById($id);

        if ($order->is_stock_sent || $order->reception()->exists()) {
            throw new \Exception("Este pedido ya fue enviado al stock / recibido anteriormente.");
        }

        return DB::transaction(function () use ($order) {
            // Sucursal que recibe el incremento de stock
            $targetBranchId = ($order->customer_type === \App\Models\Branch::class && $order->customer_id)
                ? $order->customer_id
                : $order->branch_id;

            foreach ($order->items as $item) {
                if ($item->product) {
                    $this->stockService->addStock($item->product, $item->quantity, $targetBranchId);
                }
            }

            $order->update([
                'is_stock_sent' => true,
                'stock_sent_at' => now(),
                'status'        => OrderStatus::Confirmed,
            ]);

            return $order->fresh(['items', 'customer']);
        });
    }

    /**
     * Actualiza el estado de pago del pedido (1 = Pagado, 2 = Pendiente)
     */
    public function updatePaymentStatus(int $id, int $paymentStatus): Order
    {
        $order = $this->getOrderById($id);

        $order->update([
            'payment_status' => $paymentStatus,
        ]);

        return $order;
    }

    protected function getValidationRules(array $data): array
    {
        $source = $data['source'] ?? null;
        $customerType = $data['customer_type'] ?? null;

        $rules = [
            'branch_id' => 'nullable|exists:branches,id',
            'status' => 'required|integer',
            'source' => 'required|integer|in:1,2,3',
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

        // El user_id solo es obligatorio si viene del Backoffice o Manual
        if ($source == OrderSource::Backoffice->value || $source == OrderSource::Manual->value) {
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
        $data = [
            'branch_id'      => $orderData['branch_id'],
            'user_id'        => $orderData['user_id'],
            'status'         => $orderData['status'],
            'payment_status' => $orderData['payment_status'] ?? 2,
            'source'         => $orderData['source'],
            'sale_id'        => $orderData['sale_id'] ?? null,
            'notes'          => $orderData['notes'] ?? null,
            'exchange_rate'  => $orderData['exchange_rate'],
            'totals'         => [],
            'customer_id'    => $orderData['customer_id'],
            'customer_type'  => $orderData['customer_type'],
        ];

        if (isset($orderData['created_at']) && !empty($orderData['created_at'])) {
            $data['created_at'] = $orderData['created_at'];
        }

        return Order::create($data);
    }

    protected function updateOrderRecord(Order $order, array $orderData): void
    {
        $data = [
            'branch_id'     => $orderData['branch_id'],
            'user_id'       => $orderData['user_id'],
            'status'        => $orderData['status'],
            'source'        => $orderData['source'],
            'sale_id'       => $orderData['sale_id'] ?? null,
            'notes'         => $orderData['notes'] ?? null,
            'customer_id'   => $orderData['customer_id'],
            'customer_type' => $orderData['customer_type'],
        ];

        if (isset($orderData['payment_status'])) {
            $data['payment_status'] = $orderData['payment_status'];
        }

        if (isset($orderData['created_at']) && !empty($orderData['created_at'])) {
            $data['created_at'] = $orderData['created_at'];
        }

        $order->update($data);
    }



    /**
     * Consulta productos de la sucursal activa e identifica sugerencias de auto-pedido.
     */
    public function getAutoOrderProducts(int $receivingBranchId, int $supplyingBranchId): array
    {
        $products = \App\Models\Product::with(['productBranches'])->get();

        $result = [];

        foreach ($products as $product) {
            $stockDestination = $product->getStock($receivingBranchId);
            $stockOrigin = $product->getStock($supplyingBranchId);
            $threshold = $product->productBranches->where('branch_id', $receivingBranchId)->first()?->low_stock_threshold ?? 5;

            if ($stockDestination <= $threshold) {
                $suggestedQty = max(1, $threshold - $stockDestination);
                $priceModel = $product->salePriceModel($supplyingBranchId) ?? $product->purchasePriceModel($supplyingBranchId);
                $price = $priceModel?->amount ?? 0;
                $currency = $priceModel?->currency ?? CurrencyType::ARS;

                $result[] = [
                    'id'                 => $product->id,
                    'name'               => $product->name,
                    'code'               => $product->code,
                    'stock_destination'  => $stockDestination,
                    'stock_origin'       => $stockOrigin,
                    'threshold'          => $threshold,
                    'suggested_quantity' => $suggestedQty,
                    'unit_price'         => $price,
                    'currency'           => [
                        'code'   => is_object($currency) ? $currency->value : $currency,
                        'symbol' => is_object($currency) ? $currency->symbol() : '$',
                    ],
                ];
            }
        }

        return $result;
    }
}
