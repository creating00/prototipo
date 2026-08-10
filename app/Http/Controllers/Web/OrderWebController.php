<?php

namespace App\Http\Controllers\Web;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Http\Controllers\BaseOrderController;
use App\Models\Order;
use App\Services\BranchService;
use App\Services\CategoryService;
use App\Services\ClientService;
use App\Services\CurrencyExchangeService;
use App\Traits\AuthTrait;
use Illuminate\Http\Request;

class OrderWebController extends BaseOrderController
{
    use AuthTrait;

    public function index(CurrencyExchangeService $exchangeService)
    {
        // if ($redirect = $this->redirectIfNotAdmin('web.orders.create-branch')) {
        //     return $redirect;
        // }

        $this->authorize('viewAny', Order::class);

        $rowData = $this->orderService->getAllOrdersForDataTable($this->currentUser());
        // $orders = $this->orderService->getAllOrders();

        $currentRate = $exchangeService->getCurrentDollarRate();

        $banks = \App\Models\Bank::query()
            ->orderBy('name')
            ->pluck('name', 'id');

        $bankAccounts = \App\Models\BankAccount::with(['bank', 'user'])
            ->get()
            ->pluck('full_description', 'id');

        $headers = ['#', 'Pedido', 'Sucursal', 'Cliente', 'Total', 'Canal', 'Estado', 'Estado Pago', 'Creado en:'];
        $hiddenFields = [
            'id',
            'status_raw',
            'source_raw',
            'phone',
            'whatsapp-url',
            'customer_type',
            'totals_json',
            'customer_name_raw',
            'requires_invoice',
            'payment_type',
            'total_ars',
            'total_usd',
            'requires_invoice_raw',
            'exchange_rate',
            'sale_id',
            'payments_detailed'
        ];

        return view('admin.order.index', compact(
            // 'orders',
            'rowData',
            'headers',
            'hiddenFields',
            'currentRate',
            'banks',
            'bankAccounts'
        ));
    }

    public function purchaseDetails($id)
    {
        // 1. Obtenemos el pedido
        $order = $this->orderService->getOrderById($id);
        $this->authorize('view', $order);

        // 2. Obtenemos los datos de la tabla de items (trae headers, rowData y hiddenFields)
        $itemsData = $this->orderService->getOrderItemsData($order);

        // 3. Definimos la ruta de retorno específica
        $backUrl = route('web.orders.purchases');

        $banks = \App\Models\Bank::query()
            ->orderBy('name')
            ->pluck('name', 'id');

        $bankAccounts = \App\Models\BankAccount::with(['bank', 'user'])
            ->get()
            ->pluck('full_description', 'id');

        // 4. Retornamos la vista uniendo todo
        return view('admin.order.details', [
            'order'        => $order,
            'backUrl'      => $backUrl,
            'banks'        => $banks,
            'bankAccounts' => $bankAccounts,
            'rowData'      => $itemsData['rowData'],
            'headers'      => $itemsData['headers'],
            'hiddenFields' => $itemsData['hiddenFields'],
        ]);
    }

    public function show($id)
    {
        $order = $this->orderService->getOrderById($id);
        $this->authorize('view', $order);

        $itemsData = $this->orderService->getOrderItemsData($order);

        $backUrl = route('web.orders.index');

        $banks = \App\Models\Bank::query()
            ->orderBy('name')
            ->pluck('name', 'id');

        $bankAccounts = \App\Models\BankAccount::with(['bank', 'user'])
            ->get()
            ->pluck('full_description', 'id');

        return view('admin.order.details', [
            'order'        => $order,
            'backUrl'      => $backUrl,
            'banks'        => $banks,
            'bankAccounts' => $bankAccounts,
            'rowData'      => $itemsData['rowData'],
            'headers'      => $itemsData['headers'],
            'hiddenFields' => $itemsData['hiddenFields'],
        ]);
    }

    public function purchases()
    {
        $this->authorize('viewAny', Order::class);
        $rowData = $this->orderService->getPurchasedOrdersForDataTable();

        $headers = [
            '#',
            'Proveedor (Sucursal)',
            'Total',
            'Canal',
            'Estado',
            'Estado Pago',
            'Fecha Solicitud',
            'Fecha Recepción',
            'Recibido por'
        ];

        $hiddenFields = [
            'id',
            'status_raw',
            'source_raw',
            'customer',
            'phone',
            'whatsapp-url',
            'customer_type',
            'observation',
            'is_received',
            'total_ars',
            'total_usd',
        ];

        return view('admin.order.purchases', compact('rowData', 'headers', 'hiddenFields'));
    }

    public function receive(Request $request, int $id)
    {
        $order = $this->orderService->getOrderById($id);
        $this->authorize('approve', $order);
        try {
            // Solo pasamos lo que el usuario envía, el Service decide el Status
            $data = [
                'observation' => $request->observation,
                'user_id'     => $this->userId(),
            ];

            $this->orderService->registerReception($id, $data);

            return response()->json([
                'success' => true,
                'message' => 'La recepción ha sido registrada y el inventario actualizado correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function create()
    {
        $userBranchId = $this->currentBranchId();
        $branches = app(\App\Services\BranchService::class)->getAllBranches();
        $categories = app(\App\Services\CategoryService::class)->getAllCategories();
        $clients = app(\App\Services\ClientService::class)->getAllClients($userBranchId);
        $statusOptions = OrderStatus::forSelect();

        return view('admin.order.create', compact(
            'branches',
            'categories',
            'clients',
            'statusOptions',
        ));
    }

    public function createClient()
    {
        $this->authorize('create_client', Order::class);
        $currentBranchId = $this->currentBranchId();
        $branchService = app(BranchService::class);
        $branches = collect($branchService->getAllBranches());
        $destinationBranches = collect($branchService->getAllBranchesExcept($currentBranchId));
        $clients = collect(app(ClientService::class)->getAllClients($currentBranchId));
        $statusOptions = OrderStatus::forSale();
        $customer_type = 'App\Models\Client';

        $defaultDoc = config('app.default_client_document');
        $defaultClientId = $clients->where('document', $defaultDoc)->first()?->id;

        return view('admin.order.create-client', compact(
            'customer_type',
            'branches',
            'destinationBranches',
            'clients',
            'statusOptions',
            'defaultClientId',
            'currentBranchId',
        ));
    }

    public function createBranch()
    {
        $this->authorize('create_branch', Order::class);
        $userBranchId = $this->currentBranchId();
        $branchService = app(BranchService::class);
        $user = $this->currentUser();
        $accessibleBranches = $user ? $branchService->getAccessibleBranchesForUser($user) : collect();

        $originBranch = ($userBranchId ? $branchService->getUserBranch($userBranchId) : null)
            ?? $accessibleBranches->first()
            ?? \App\Models\Branch::first();

        $currentBranchId = $originBranch?->id;
        $destinationBranches = collect($branchService->getAllBranchesExcept($currentBranchId));

        $statusOptions = OrderStatus::forInternalOrder();
        $customer_type = 'App\Models\Branch';

        return view('admin.order.create-branch', compact(
            'customer_type',
            'originBranch',
            'currentBranchId',
            'destinationBranches',
            'statusOptions'
        ) + ['isEdit' => false, 'order' => null]);
    }

    public function store(Request $request)
    {
        if ($request->customer_type === 'App\Models\Branch') {
            $this->authorize('createBranch', Order::class);
        } else {
            $this->authorize('createClient', Order::class);
        }

        try {
            $data = $request->all();

            // Si el formulario solicita explícitamente source = Manual (3), mantenemos Manual.
            // De lo contrario, asignamos Backoffice.
            if (isset($data['source']) && (int)$data['source'] === OrderSource::Manual->value) {
                $data['source'] = OrderSource::Manual->value;
            } else {
                $data['source'] = isset($data['source']) ? (int)$data['source'] : OrderSource::Backoffice->value;
            }

            $data['user_id'] = $this->userId();
            $order = $this->orderService->createOrder($data);

            // Redirección inteligente: si es pedido a sucursal o manual, va a Mis Pedidos Realizados
            if ((int)$data['source'] === OrderSource::Manual->value || $request->customer_type === 'App\Models\Branch') {
                return redirect()
                    ->route('web.orders.purchases')
                    ->with('success', 'Pedido solicitado correctamente.');
            }

            return redirect()
                ->route('web.orders.index')
                ->with('success', 'Orden de venta creada exitosamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        }
    }

    public function sendToStock(int $id)
    {
        try {
            $order = $this->orderService->sendToStock($id);

            return response()->json([
                'success' => true,
                'message' => 'El pedido fue enviado al stock e incrementó el inventario correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function updatePaymentStatus(Request $request, int $id)
    {
        $request->validate([
            'payment_status' => 'required|integer|in:1,2',
        ]);

        try {
            $order = $this->orderService->updatePaymentStatus($id, (int) $request->payment_status);

            return response()->json([
                'success' => true,
                'message' => 'Estado de pago actualizado correctamente.',
                'payment_status_label' => $order->payment_status_label,
                'payment_status_badge' => $order->payment_status_badge_class,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function autoOrderItems(Request $request)
    {
        $receivingBranchId = (int) ($request->get('customer_id') ?: $this->currentBranchId());
        $supplyingBranchId = (int) ($request->get('branch_id') ?: $this->currentBranchId());

        $items = $this->orderService->getAutoOrderProducts($receivingBranchId, $supplyingBranchId);

        return response()->json([
            'success' => true,
            'items'   => $items,
        ]);
    }

    public function edit($id)
    {
        $order = $this->orderService->getOrderById($id);
        $this->authorize('update', $order);

        if (!$order->canBeEdited()) {
            return redirect()->back()->with('error', 'El registro de este pedido está bloqueado porque ya fue enviado al stock.');
        }

        $userBranchId = $this->currentBranchId();

        $isEdit = true;

        $customer_type = $order->customer_type;
        $branchService = app(BranchService::class);

        // Inicializamos variables vacías/nulas
        $branches = collect();
        $originBranch = null;
        $destinationBranches = collect();
        $statusOptions = [];

        if ($customer_type === 'App\Models\Branch') {
            // --- 1. LÓGICA PARA SUCURSALES (COMPRAS INTERNAS) ---
            $branches = collect([
                $branchService->getUserBranch($order->customer_id)
            ]);

            // Sucursales Proveedoras (Todas excepto la que pide)
            $destinationBranches = collect($branchService->getAllBranchesExcept($order->customer_id));

            $originBranch = $branchService->getUserBranch($order->branch_id);
            $statusOptions = OrderStatus::forInternalOrder();
        } else {
            // --- 2. LÓGICA PARA CLIENTES (VENTAS) ---
            $branchIdToUse = $order->branch_id ?? $userBranchId;

            $branches = collect([
                $branchService->getUserBranch($branchIdToUse)
            ]);

            $statusOptions = OrderStatus::forSale();
        }

        return view('admin.order.edit', [
            'order'               => $order,
            'isEdit'              => $isEdit,
            'customer_type'       => $customer_type,
            'existingOrderItems'  => $this->orderService->buildOrderItemsHtml($order),
            'branches'            => $branches,
            'currentBranchId'     => $userBranchId,
            'categories'          => app(CategoryService::class)->getAllCategories(),
            'clients'             => app(ClientService::class)->getAllClients($userBranchId),
            'statusOptions'       => $statusOptions,
            'originBranch'        => $originBranch,
            'destinationBranches' => $destinationBranches,
        ]);
    }

    public function update(Request $request, $id)
    {
        $order = $this->orderService->getOrderById($id);
        $this->authorize('update', $order);

        try {
            $order = $this->orderService->updateOrder($id, $request->all());

            $userBranchId = $this->currentBranchId();

            if ($order->customer_type === \App\Models\Branch::class) {

                // Soy sucursal ORIGEN (envía)
                if ((int) $order->branch_id === (int) $userBranchId) {
                    return redirect()
                        ->route('web.orders.index')
                        ->with('success', 'Pedido actualizado correctamente.');
                }

                // Soy sucursal DESTINO (recibe / compra)
                if ((int) $order->customer_id === (int) $userBranchId) {
                    return redirect()
                        ->route('web.orders.purchases')
                        ->with('success', 'Pedido actualizado correctamente.');
                }
            }

            // Pedido a cliente
            return redirect()
                ->route('web.orders.index')
                ->with('success', 'Orden actualizada correctamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->validator)
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $order = $this->orderService->getOrderById($id);

            $this->authorize('delete', $order);

            // Determinar ruta de redirección
            $redirectRoute = ($order->customer_type === \App\Models\Branch::class)
                ? 'web.orders.purchases'
                : 'web.orders.index';

            $this->orderService->deleteOrder($id);

            return redirect()
                ->route($redirectRoute)
                ->with('success', 'Orden eliminada exitosamente');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'No se pudo eliminar la orden: ' . $e->getMessage());
        }
    }

    public function cancel($id)
    {
        try {
            $order = $this->orderService->getOrderById($id);

            $this->authorize('cancel', $order);

            $redirectRoute = ($order->customer_type === 'App\Models\Branch')
                ? 'web.orders.purchases'
                : 'web.orders.index';

            $this->orderService->cancelOrder($id);

            return redirect()
                ->route($redirectRoute)
                ->with('success', 'Orden cancelada exitosamente');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'No se pudo cancelar la orden: ' . $e->getMessage());
        }
    }
}
