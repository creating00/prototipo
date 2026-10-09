<?php

namespace App\Services;

use App\Enums\CurrencyType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\RoleLabel;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReception;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use App\Services\Order\OrderDataProcessor;
use App\Services\Order\OrderItemProcessor;
use App\Services\Order\OrderTransferService;
use App\Services\Product\ProductStockService;
use App\Services\Traits\DataTableFormatter;
use App\Traits\AuthTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderService
{
    use AuthTrait, DataTableFormatter;

    protected ProductStockService $stockService;

    protected OrderDataProcessor $dataProcessor;

    protected OrderItemProcessor $itemProcessor;

    protected ClientService $clientService;

    public function __construct(
        ProductStockService $stockService,
        ?OrderDataProcessor $dataProcessor = null,
        ?OrderItemProcessor $itemProcessor = null,
        ?ClientService $clientService = null
    ) {
        $this->stockService = $stockService;
        $this->dataProcessor = $dataProcessor ?? new OrderDataProcessor;
        $this->itemProcessor = $itemProcessor ?? new OrderItemProcessor($stockService);
        $this->clientService = $clientService ?? new ClientService;
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

        if ($this->currentUser() && ! $branchId) {
            $accessibleIds = $this->getAccessibleBranchIds();
            $query->where(fn ($q) => $q->where('is_multi_destination', false)->orWhereIn('branch_id', $accessibleIds));
        } elseif (! $this->currentUser()) {
            $query->where('is_multi_destination', false);
        }

        // Lógica de Rol: Si es Seller, solo ve pedidos de Clientes
        if ($user && $user->hasRole('seller')) {
            $query->forClientsOnly();
        }

        return $query->get();
    }

    public function getOrderById($id): Order
    {
        return Order::with(['items.product', 'items.destinationBranch', 'payments', 'customer', 'branch'])
            ->findOrFail($id);
    }

    public function visibleItems(Order $order): \Illuminate\Database\Eloquent\Collection
    {
        $branchId = $this->currentBranchId();
        if (! $order->is_multi_destination || (int) $order->branch_id === $branchId) {
            return $order->items;
        }
        if ($branchId) {
            return $order->items->where('destination_branch_id', $branchId)->values();
        }
        $accessibleIds = $this->getAccessibleBranchIds();
        if (in_array((int) $order->branch_id, $accessibleIds, true)) {
            return $order->items;
        }

        return $order->items->whereIn('destination_branch_id', $accessibleIds)->values();
    }

    public function forDisplay(Order $order): Order
    {
        if (! $order->is_multi_destination) {
            return $order;
        }
        $display = clone $order;
        $items = $this->visibleItems($order);
        $display->setRelation('items', $items);
        $display->totals = $items->groupBy(fn ($item) => $item->currency->value)
            ->map(fn ($lines) => (float) $lines->sum('subtotal'))->all();

        return $display;
    }

    public function buildOrderItemsHtml(Order $order): array
    {
        $destinationBranches = Branch::when($order->source !== OrderSource::Manual, fn ($q) => $q->where('id', '!=', $order->branch_id))->orderBy('name')->get();

        return $order->items->map(function ($item) use ($order, $destinationBranches) {
            return [
                'html' => view('admin.order.partials._item_row', [
                    'product' => $item->product,
                    'item' => $item,
                    'stock' => $item->product ? $item->product->getStock($order->branch_id) : 0,
                    'salePrice' => $item->unit_price,
                    'branchId' => $order->branch_id,
                    'isMultiDestination' => $order->is_multi_destination,
                    'destinationBranches' => $destinationBranches,
                ])->render(),
            ];
        })->values()->toArray();
    }

    public function buildDraftItemsHtml(array $items, int $branchId): array
    {
        $branches = Branch::orderBy('name')->get();
        $rows = [];
        foreach ($items as $data) {
            if (! is_array($data)) {
                continue;
            }
            $product = Product::find($data['product_id'] ?? null);
            if (! $product) {
                continue;
            }
            $item = new OrderItem([
                'product_id' => $product->id,
                'quantity' => (int) ($data['quantity'] ?? 1),
                'unit_price' => (float) ($data['unit_price'] ?? 0),
                'currency' => CurrencyType::tryFrom((int) ($data['currency'] ?? 1)) ?? CurrencyType::ARS,
                'destination_branch_id' => $data['destination_branch_id'] ?? null,
            ]);
            $rows[] = ['html' => view('admin.order.partials._item_row', [
                'product' => $product, 'item' => $item, 'stock' => $product->getStock($branchId),
                'branchId' => $branchId, 'isMultiDestination' => true, 'destinationBranches' => $branches,
            ])->render()];
        }

        return $rows;
    }

    public function createOrder(array $data): Order
    {
        // Forzar estado del pedido por defecto a Pendiente
        if (! isset($data['status'])) {
            $data['status'] = OrderStatus::Pending->value;
        }

        // El estado de pago siempre inicia en Pendiente (2)
        $data['payment_status'] = 2;

        $validated = $this->validateOrderData($data);

        return DB::transaction(function () use ($validated, $data) {
            $orderData = $this->dataProcessor->prepare($validated);
            if (! empty($orderData['is_multi_destination'])) {
                app(OrderTransferService::class)->validateDestinations($orderData);
            }
            $orderData['payment_status'] = 2;
            if (isset($data['created_at']) && ! empty($data['created_at'])) {
                $orderData['created_at'] = $data['created_at'];
            }
            $orderData['items'] = $this->normalizeOrderItemCostsForUser(
                $orderData['items'],
                (int) $orderData['branch_id'],
                null,
                $orderData['customer_type'] ?? null
            );
            $order = $this->createOrderRecord($orderData);

            // El registro inicial NO descuenta stock (skipStockMovement = true)
            $totals = $order->is_multi_destination
                ? $this->itemProcessor->syncDestinations($order, $orderData['items'])
                : $this->itemProcessor->sync($order, $orderData['items'], true);

            $order->update([
                'totals' => $totals,
            ]);

            $order = $order->fresh(['items', 'customer']);

            $itemsCount = $order->items->sum('quantity');
            $customerName = $order->customer_name;

            $message = "{$customerName} hizo un pedido de {$itemsCount} productos.";

            $targetUsers = User::where('branch_id', $order->branch_id)->get();
            if ($order->is_multi_destination) {
                app(OrderTransferService::class)->notifyParticipants($order);
            } elseif ($targetUsers->isNotEmpty()) {
                Notification::send($targetUsers, new OrderStatusNotification($order, $message));
            }

            return $order->fresh();
        });
    }

    public function updateOrder($id, array $data): Order
    {
        $order = $this->getOrderById($id);

        if (! $order->canBeEdited()) {
            throw new \Exception('No se puede modificar un pedido que ya ha sido enviado al stock.');
        }

        $data['is_multi_destination'] = $order->is_multi_destination;
        if ($order->is_multi_destination) {
            $data['branch_id'] = $order->branch_id;
            $data['customer_type'] = $order->customer_type;
            $data['source'] = $order->source->value;
            $data['user_id'] = $order->user_id;
        }
        $validated = $this->validateOrderData($data, $order->id);

        return DB::transaction(function () use ($order, $validated, $data) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! $order->canBeEdited()) {
                throw ValidationException::withMessages(['order' => 'El pedido ya no puede modificarse.']);
            }
            $orderData = $this->dataProcessor->prepare($validated, $order);
            if ($order->is_multi_destination) {
                app(OrderTransferService::class)->validateDestinations($orderData);
            }
            if (isset($data['created_at']) && ! empty($data['created_at'])) {
                $orderData['created_at'] = $data['created_at'];
            }
            $orderData['items'] = $this->normalizeOrderItemCostsForUser(
                $orderData['items'],
                (int) $orderData['branch_id'],
                $order,
                $orderData['customer_type'] ?? $order->customer_type
            );

            // Mantenemos sin descuento de stock durante las modificaciones antes de enviar al stock
            $totals = $order->is_multi_destination
                ? $this->itemProcessor->syncDestinations($order, $orderData['items'])
                : $this->itemProcessor->sync($order, $orderData['items'], true);

            $this->updateOrderRecord($order, $orderData);

            $order->update([
                'totals' => $totals,
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
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->is_stock_sent || $order->sale_id || ($order->is_multi_destination && $order->status === OrderStatus::Cancelled)) {
                throw ValidationException::withMessages(['order' => 'No se puede eliminar un pedido con movimientos de stock o una venta asociada.']);
            }

            $order->items()->delete();
            $order->delete();

            return ['message' => 'Order deleted'];
        });
    }

    public function cancelOrder($id, ?string $reason = null): array
    {
        $order = $this->getOrderById($id);

        // Evitar procesar si ya está cancelada
        if ($order->status === OrderStatus::Cancelled) {
            throw new \Exception('La orden ya se encuentra cancelada.', 400);
        }

        return DB::transaction(function () use ($order, $reason) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->is_stock_sent || $order->sale_id || $order->reception()->exists()
                || in_array($order->status, [OrderStatus::Cancelled, OrderStatus::ConvertedToSale], true)) {
                throw ValidationException::withMessages(['order' => 'No se puede cancelar un pedido procesado.']);
            }

            $reason = trim($reason ?? '');
            Validator::make(['cancellation_reason' => $reason], [
                'cancellation_reason' => [$order->is_multi_destination ? 'required' : 'nullable', 'string', 'max:1000'],
            ])->validate();
            if ($order->is_multi_destination && ! $this->userId()) {
                throw ValidationException::withMessages(['order' => 'Debe iniciar sesión para cancelar el pedido.']);
            }

            // Pending orders have never reserved stock; cancellation must not add inventory.

            // 2. Actualizar el estado usando el Enum
            $order->update([
                'status' => OrderStatus::Cancelled,
                'cancellation_reason' => $reason ?: null,
                'cancelled_at' => now(),
                'cancelled_by' => $this->userId(),
            ]);

            return ['message' => 'Pedido cancelado sin modificar el stock.'];
        });
    }

    /**
     * Convierte una orden en venta permitiendo personalizar el pago y el usuario.
     *
     * @param  int  $id  ID de la orden
     * @param  array  $options  Datos opcionales (payment_type, user_id, amount_received)
     * @return Sale
     */
    public function convertToSale($id, array $options = [])
    {
        // dd($options); // Comentado para permitir el flujo

        return DB::transaction(function () use ($id, $options) {
            $order = Order::whereKey($id)->lockForUpdate()->firstOrFail();
            $order->load('items');

            if ($order->is_multi_destination) {
                throw ValidationException::withMessages(['order' => 'Los pedidos con destinos por producto se procesan mediante Enviar al stock. No se pueden convertir en una venta de un único cliente.']);
            }

            if ($order->status === OrderStatus::ConvertedToSale) {
                throw new \Exception('Esta orden ya fue convertida a venta.');
            }

            // 1. Tasa y determinación de moneda
            $rate = (float) ($options['exchange_rate_blue'] ?? app(CurrencyExchangeService::class)->getCurrentDollarRate());
            $arsKey = \App\Enums\CurrencyType::ARS->value;
            $usdKey = \App\Enums\CurrencyType::USD->value;

            $isPayingInUsd = ! empty($options['total_amount_usd']);
            $totalConsolidado = $isPayingInUsd
                ? (float) $options['total_amount_usd']
                : (float) ($options['total_amount'] ?? 0);

            $currencyId = $isPayingInUsd ? $usdKey : $arsKey;
            $userId = $options['user_id'] ?? $this->userId();

            if (! $userId) {
                throw new \Exception('No se pudo determinar el usuario.');
            }

            // 2. Mapeo de ítems
            $items = $order->items->map(function ($i) {
                return [
                    'product_id' => $i->product_id,
                    'quantity' => $i->quantity,
                    'unit_price' => $i->unit_price,
                    'currency' => is_object($i->currency) ? $i->currency->value : $i->currency,
                ];
            })->values()->toArray();

            if (empty($items)) {
                throw new \Exception('Error: La orden no tiene ítems cargados.');
            }

            // 3. Preparación de datos de venta y pagos
            $isDual = (bool) ($options['is_dual_payment'] ?? false);

            $data = [
                'source_order_id' => $order->id,
                'branch_id' => $order->branch_id,
                'user_id' => $userId,
                'customer_type' => $order->customer_type,
                'sale_type' => \App\Enums\SaleType::Sale->value,
                'sale_date' => now()->format('Y-m-d'),
                'status' => \App\Enums\SaleStatus::Paid->value,
                'items' => $items,
                'currency_id' => $currencyId,
                'exchange_rate_blue' => $rate,
                'totals' => json_encode([$currencyId => $totalConsolidado]),

                // Flags para HandlesSalePayments
                'enable_dual_payment' => $isDual ? 1 : 0,
                'requires_invoice' => ! empty($options['requires_invoice']),

                // Pago 1: Normalizado (Viene de _1 gracias a la sincronización JS)
                'payment_type' => $options['payment_type_1'] ?? null,
                'amount_received' => (float) ($options['amount_received_1'] ?? 0),
                'payment_method_id' => $options['bank_account_id_1'] ?? $options['bank_id_1'] ?? null,
                'payment_notes' => $options['payment_notes'] ?? null,
            ];

            // Pago 2: Solo si es dual
            if ($isDual) {
                $data['payment_type_2'] = $options['payment_type_2'] ?? null;
                $data['amount_received_2'] = (float) ($options['amount_received_2'] ?? 0);
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
                'status' => OrderStatus::ConvertedToSale->value,
                'sale_id' => $sale->id,
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
            fn ($order, $index) => $this->formatOrderForDataTable($order, $index)
        )->toArray();
    }

    /**
     * Prepara los datos de los items del pedido para la DataTable en la vista de detalle.
     */
    public function getOrderItemsData(Order $order): array
    {
        $headers = ['N°', 'Producto', 'Stock', 'Precio', 'Cantidad', 'Subtotal'];
        if ($order->is_multi_destination || $order->isInterBranch()) {
            $headers[] = 'Sucursal destino';
        }

        $items = $this->visibleItems($order);
        $rowData = $items->map(function ($item, $index) use ($order) {
            $stock = $item->product ? $item->product->getStock($order->branch_id) : 0;

            $row = [
                'id' => $item->id,
                'number' => $index + 1,
                'product' => $item->product?->name ?? '<span class="text-muted fst-italic">Producto eliminado</span>',
                'stock' => '<span class="badge bg-secondary">'.$stock.'</span>',
                'unit_price' => $this->formatCurrency(
                    $item->unit_price,
                    $item->currency,
                    'text-dark'
                ),
                'quantity' => $item->quantity,
                'subtotal' => $this->formatCurrency(
                    $item->subtotal,
                    $item->currency,
                    'fw-bold text-dark'
                ),
            ];
            if ($order->is_multi_destination || $order->isInterBranch()) {
                $destination = $order->is_multi_destination ? $item->destinationBranch : $order->customer;
                $row['destination'] = e($destination?->name ?? 'Sucursal no disponible');
            }

            return $row;
        })->toArray();

        return [
            'headers' => $headers,
            'rowData' => $rowData,
            'hiddenFields' => ['id'],
        ];
    }

    /**
     * Obtiene las órdenes donde la sucursal actual es el cliente (Compras Internas).
     */
    public function getPurchasedOrders()
    {
        $branchId = $this->currentBranchId();

        $query = Order::with(['branch', 'customer', 'user', 'reception', 'items.destinationBranch'])
            ->where(function ($q) {
                $q->where('customer_type', \App\Models\Branch::class)
                    ->orWhere('source', OrderSource::Manual);
            })
            ->orderBy('created_at', 'desc');

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('customer_id', $branchId)
                    ->orWhere(function ($transfer) use ($branchId) {
                        $transfer->where('is_multi_destination', true)
                            ->where(fn ($participants) => $participants->where('branch_id', $branchId)
                                ->orWhereHas('items', fn ($items) => $items->where('destination_branch_id', $branchId)));
                    });
            });
        }

        if ($this->currentUser() && ! $branchId) {
            $accessibleIds = $this->getAccessibleBranchIds();
            $query->where(fn ($q) => $q->where('is_multi_destination', false)
                ->orWhere(fn ($transfer) => $transfer->where('is_multi_destination', true)
                    ->where(fn ($participants) => $participants->whereIn('branch_id', $accessibleIds)
                        ->orWhereHas('items', fn ($items) => $items->whereIn('destination_branch_id', $accessibleIds)))));
        }

        return $query->get();
    }

    /**
     * Formatea las compras para la DataTable.
     */
    public function getPurchasedOrdersForDataTable(): array
    {
        return $this->getPurchasedOrders()->map(function ($order, $index) {
            $order = $this->forDisplay($order);
            $reception = $order->reception;
            $statusSource = $reception ?? $order;
            $isStockSent = (bool) $order->is_stock_sent || (bool) $reception;

            $sourceRaw = is_object($order->source) ? $order->source->value : $order->source;
            $sourceLabel = is_object($order->source) ? $order->source->label() : $order->source;
            $sourceBadgeClass = match ((int) $sourceRaw) {
                \App\Enums\OrderSource::Ecommerce->value => 'bg-info',
                \App\Enums\OrderSource::Manual->value => 'badge-purple',
                default => 'bg-secondary',
            };

            return [
                'id' => $order->id,
                'status_raw' => is_object($statusSource->status) ? $statusSource->status->value : $statusSource->status,
                'source_raw' => $sourceRaw,
                'is_received' => $isStockSent ? 'true' : 'false',
                'customer' => $this->resolveCustomerName($order),
                'customer_type' => $order->customer_type,
                'phone' => $this->cleanPhoneNumber($order->customer?->phone),
                'observation' => $reception ? ($reception->observation ?? 'Sin notas') : '---',
                'number' => $index + 1,                                  // #
                'order_number' => $order->order_number,
                'branch' => $order->branch->name ?? 'N/A',               // Proveedor
                'total' => collect($order->totals)
                    ->map(fn ($v, $k) => $this->formatCurrency($v, CurrencyType::from($k)))
                    ->implode(' / '),
                'source' => '<span class="badge '.$sourceBadgeClass.'">'.$sourceLabel.'</span>', // Canal
                'status' => $this->resolveStatus($statusSource, ['currencyClass' => 'fw-bold text-info']), // Estado
                'payment_status' => sprintf('<span class="%s">%s</span>', $order->payment_status_badge_class, $order->payment_status_label), // Estado Pago
                'created_at' => $order->created_at->format('d-m-Y'),         // Fecha Solicitud
                'received_at' => $reception ? $reception->received_at->format('d-m-Y H:i') : ($order->stock_sent_at ? $order->stock_sent_at->format('d-m-Y H:i') : '---'),
                'received_by' => $order->user->name ?? ($reception?->user?->name ?? '---'),
                '_row_attributes' => [
                    'id' => $order->id,
                    'status_raw' => is_object($statusSource->status) ? $statusSource->status->value : $statusSource->status,
                    'source_raw' => $sourceRaw,
                    'is_received' => $isStockSent ? 'true' : 'false',
                    'can_edit' => (! $order->is_multi_destination && $order->canBeEdited() && ! $isStockSent) ? 'true' : 'false',
                    'is_multi_destination' => $order->is_multi_destination ? 'true' : 'false',
                ],
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
            $order = Order::with('items.product')->whereKey($orderId)->lockForUpdate()->firstOrFail();
            if ($order->is_multi_destination) {
                throw ValidationException::withMessages(['order' => 'El origen debe procesar este pedido mediante Enviar al stock.']);
            }

            // 2. Validaciones de integridad
            if ($order->customer_type !== \App\Models\Branch::class) {
                throw new \Exception('Solo los pedidos entre sucursales requieren registro de recepción.');
            }

            if ($order->is_stock_sent || $order->reception()->exists()) {
                throw new \Exception('Este pedido ya fue enviado al stock / recibido anteriormente.');
            }

            // 3. Determinar el estado basado en la observación
            // Si hay texto en 'observation', usamos ReceivedWithIssues, de lo contrario Received
            $status = ! empty($data['observation'])
                ? \App\Enums\OrderReceptionStatus::ReceivedWithIssues
                : \App\Enums\OrderReceptionStatus::Received;

            // 4. Crear el registro de recepción
            $reception = $order->reception()->create([
                'user_id' => $this->userId() ?? $data['user_id'],
                'status' => $status,
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
                    (float) $item->unit_price,
                    is_object($item->currency) ? $item->currency->value : $item->currency
                );
            }

            // 6. Actualizar el estado del Pedido a enviado al stock y confirmado
            $order->update([
                'is_stock_sent' => true,
                'stock_sent_at' => now(),
                'status' => OrderStatus::Confirmed,
            ]);

            return $reception;
        });
    }

    /**
     * Incrementa el stock de la sucursal según el pedido y bloquea la orden para modificaciones.
     */
    public function sendToStock(int $id): Order
    {
        return DB::transaction(function () use ($id) {
            $order = Order::with('items.product')->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($order->is_stock_sent || $order->reception()->exists()) {
                throw ValidationException::withMessages(['stock' => 'Este pedido ya fue enviado al stock / recibido anteriormente.']);
            }
            if (in_array($order->status, [OrderStatus::Cancelled, OrderStatus::ConvertedToSale], true)) {
                throw ValidationException::withMessages(['stock' => 'No se puede enviar al stock un pedido cancelado o convertido en venta.']);
            }
            if ($order->is_multi_destination) {
                app(OrderTransferService::class)->transfer($order);

                return $order->fresh(['items.destinationBranch', 'branch']);
            }
            // Sucursal que recibe el incremento de stock
            $targetBranchId = ($order->customer_type === \App\Models\Branch::class && $order->customer_id)
                ? $order->customer_id
                : $order->branch_id;

            foreach ($order->items as $item) {
                if ($item->product) {
                    $this->stockService->addStock($item->product, $item->quantity, $targetBranchId);

                    $currency = is_object($item->currency)
                        ? $item->currency->value
                        : (int) $item->currency;

                    $this->stockService->updatePurchasePrice(
                        $item->product,
                        $targetBranchId,
                        (float) $item->unit_price,
                        $currency
                    );
                }
            }

            $order->update([
                'is_stock_sent' => true,
                'stock_sent_at' => now(),
                'status' => OrderStatus::Confirmed,
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
        $multiDestination = filter_var($data['is_multi_destination'] ?? false, FILTER_VALIDATE_BOOLEAN);

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
            'is_multi_destination' => 'sometimes|boolean',
        ];

        // Validación por tipo de Cliente
        if ($multiDestination) {
            $isManual = (int) $source === OrderSource::Manual->value;
            $rules['customer_type'] = ['required', Rule::in([$isManual ? Client::class : Branch::class])];
            if ($isManual) {
                $rules['client_id'] = 'required|exists:clients,id';
                $rules['target_branch_id'] = ['required', Rule::exists('branches', 'id')->whereNull('deleted_at')];
            }
            $rules['branch_id'] = ['required', Rule::exists('branches', 'id')->whereNull('deleted_at')];
            $rules['status'] = ['required', Rule::in([OrderStatus::Draft->value, OrderStatus::Pending->value])];
            $rules['items.*.quantity'] = 'required|integer|min:1';
            $rules['items.*.product_id'] = ['required', Rule::exists('products', 'id')->whereNull('deleted_at')];
            $rules['items.*.destination_branch_id'] = ['required', Rule::exists('branches', 'id')->whereNull('deleted_at')];
            $rules['items.*.destination_province_id'] = 'required|exists:provinces,id';
            $rules['customer_id'] = 'prohibited';
            $rules['branch_recipient_id'] = 'prohibited';
            $rules['source'] = ['required', Rule::in([OrderSource::Backoffice->value, OrderSource::Manual->value])];
            $rules['sale_id'] = 'prohibited';
            $rules['created_at'] = 'nullable|date';
        } elseif ($customerType === Client::class) {
            $rules['target_branch_id'] = ['nullable', Rule::exists('branches', 'id')->whereNull('deleted_at')];
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
        if (! isset($data['id'])) {
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
            'branch_id' => $orderData['branch_id'],
            'is_multi_destination' => $orderData['is_multi_destination'] ?? false,
            'target_branch_id' => $orderData['target_branch_id'] ?? null,
            'user_id' => $orderData['user_id'],
            'status' => $orderData['status'],
            'payment_status' => $orderData['payment_status'] ?? 2,
            'source' => $orderData['source'],
            'sale_id' => $orderData['sale_id'] ?? null,
            'notes' => $orderData['notes'] ?? null,
            'exchange_rate' => $orderData['exchange_rate'],
            'totals' => [],
            'customer_id' => $orderData['customer_id'],
            'customer_type' => $orderData['customer_type'],
        ];

        if (isset($orderData['created_at']) && ! empty($orderData['created_at'])) {
            $data['created_at'] = $orderData['created_at'];
        }

        return Order::create($data);
    }

    protected function updateOrderRecord(Order $order, array $orderData): void
    {
        $data = [
            'target_branch_id' => $orderData['target_branch_id'] ?? $order->target_branch_id,
            'branch_id' => $orderData['branch_id'],
            'user_id' => $orderData['user_id'],
            'status' => $orderData['status'],
            'source' => $orderData['source'],
            'sale_id' => $orderData['sale_id'] ?? null,
            'notes' => $orderData['notes'] ?? null,
            'customer_id' => $orderData['customer_id'],
            'customer_type' => $orderData['customer_type'],
        ];

        if (isset($orderData['payment_status'])) {
            $data['payment_status'] = $orderData['payment_status'];
        }

        if (isset($orderData['created_at']) && ! empty($orderData['created_at'])) {
            $data['created_at'] = $orderData['created_at'];
        }

        $order->update($data);
    }

    protected function normalizeOrderItemCostsForUser(array $items, int $branchId, ?Order $order = null, ?string $customerType = null): array
    {
        if ($this->currentUser()?->hasRole(RoleLabel::PROVINCIAL_ADMIN->value)) {
            return $items;
        }

        $existingItems = $order?->items?->keyBy(fn ($item) => $item->product_id.':'.($item->destination_branch_id ?? '')) ?? collect();

        return array_map(function (array $item) use ($branchId, $existingItems, $customerType) {
            $productId = (int) $item['product_id'];
            $existingItem = $existingItems->get($productId.':'.($item['destination_branch_id'] ?? ''));

            if ($existingItem) {
                $item['unit_price'] = (float) $existingItem->unit_price;
                $item['currency'] = is_object($existingItem->currency)
                    ? $existingItem->currency->value
                    : (int) $existingItem->currency;

                return $item;
            }

            $product = \App\Models\Product::findOrFail($productId);
            if ($customerType === 'App\Models\Branch') {
                $priceModel = $product->purchasePriceModel($branchId)
                    ?? $product->purchasePriceModel(null);
            } else {
                $priceModel = $product->salePriceModel($branchId)
                    ?? $product->salePriceModel(null)
                    ?? $product->purchasePriceModel($branchId)
                    ?? $product->purchasePriceModel(null);
            }

            $item['unit_price'] = (float) ($priceModel?->amount ?? 0);
            $item['currency'] = $priceModel?->currency?->value ?? ($item['currency'] ?? CurrencyType::ARS->value);

            return $item;
        }, $items);
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
                $priceModel = $product->purchasePriceModel($supplyingBranchId) ?? $product->purchasePriceModel(null);
                $price = $priceModel?->amount ?? 0;
                $currency = $priceModel?->currency ?? CurrencyType::ARS;

                $result[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'stock_destination' => $stockDestination,
                    'stock_origin' => $stockOrigin,
                    'threshold' => $threshold,
                    'suggested_quantity' => $suggestedQty,
                    'unit_price' => $price,
                    'currency' => [
                        'code' => is_object($currency) ? $currency->value : $currency,
                        'symbol' => is_object($currency) ? $currency->symbol() : '$',
                    ],
                ];
            }
        }

        return $result;
    }
}
