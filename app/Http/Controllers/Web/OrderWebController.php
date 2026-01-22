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
        if ($redirect = $this->redirectIfNotAdmin('web.orders.create-branch')) {
            return $redirect;
        }

        $this->authorize('viewAny', Order::class);

        $rowData = $this->orderService->getAllOrdersForDataTable();
        $orders = $this->orderService->getAllOrders();

        $currentRate = $exchangeService->getCurrentDollarRate();

        $headers = ['#', 'Sucursal', 'Cliente', 'Total', 'Estado', 'Creado en:'];
        $hiddenFields = [
            'id',
            'status_raw',
            'phone',
            'whatsapp-url',
            'customer_type',
            'totals_json',
            'customer_name_raw',
            'requires_invoice',
            'payment_type',
        ];

        return view('admin.order.index', compact('orders', 'rowData', 'headers', 'hiddenFields', 'currentRate'));
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

        // 4. Retornamos la vista uniendo todo
        return view('admin.order.details', [
            'order'        => $order,
            'backUrl'      => $backUrl,
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

        return view('admin.order.details', [
            'order'        => $order,
            'backUrl'      => $backUrl,
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
            'Estado',
            'Fecha Solicitud',
            'Fecha Recepción',
            'Recibido por'
        ];

        $hiddenFields = [
            'id',
            'status_raw',
            'customer',
            'phone',
            'whatsapp-url',
            'customer_type',
            'observation',
            'is_received',
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
        $branches = app(\App\Services\BranchService::class)->getAllBranches();
        $categories = app(\App\Services\CategoryService::class)->getAllCategories();
        $clients = app(\App\Services\ClientService::class)->getAllClients();
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
        $branches = collect(app(BranchService::class)->getAllBranches());
        $clients = collect(app(ClientService::class)->getAllClients());
        $statusOptions = OrderStatus::forSale();
        $customer_type = 'App\Models\Client';

        $defaultDoc = config('app.default_client_document');
        $defaultClientId = $clients->where('document', $defaultDoc)->first()?->id;

        return view('admin.order.create-client', compact(
            'customer_type',
            'branches',
            'clients',
            'statusOptions',
            'defaultClientId',
        ));
    }

    public function createBranch()
    {
        $this->authorize('create_branch', Order::class);
        $userBranchId = $this->currentBranchId();
        $branchService = app(BranchService::class);

        $originBranch = $branchService->getUserBranch($userBranchId);
        $destinationBranches = collect($branchService->getAllBranchesExcept($userBranchId));

        $statusOptions = OrderStatus::forInternalOrder();
        $customer_type = 'App\Models\Branch';

        return view('admin.order.create-branch', compact(
            'customer_type',
            'originBranch',
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
            $data['source'] = OrderSource::Backoffice->value;
            $data['user_id'] = $this->userId();
            $order = $this->orderService->createOrder($data);

            // Si el destino es una sucursal, es una "Compra"
            if ($request->customer_type === 'App\Models\Branch') {
                return redirect()
                    ->route('web.orders.purchases')
                    ->with('success', 'Pedido a sucursal solicitado correctamente.');
            }

            return redirect()
                ->route('web.orders.index')
                ->with('success', 'Orden de venta creada exitosamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        }
    }

    public function edit($id)
    {
        $order = $this->orderService->getOrderById($id);
        $this->authorize('update', $order);

        $isEdit = true;

        $customer_type = $order->customer_type;
        $branchService = app(BranchService::class);

        $branches = collect([
            $branchService->getUserBranch($order->customer_id)
        ]);

        // Inicializamos variables nulas para evitar el error "Undefined variable"
        $originBranch = null;
        $destinationBranches = collect();
        $statusOptions = [];

        if ($customer_type === 'App\Models\Branch') {
            // 2. Sucursales que pueden ser "Proveedoras"
            // Deben ser todas excepto la sucursal que está pidiendo (customer_id)
            $destinationBranches = collect($branchService->getAllBranchesExcept($order->customer_id));

            $originBranch = $branchService->getUserBranch($order->branch_id);
            $statusOptions = OrderStatus::forInternalOrder();
        } else {
            $statusOptions = OrderStatus::forSale();
        }

        return view('admin.order.edit', [
            'order'               => $order,
            'isEdit'              => $isEdit,
            'customer_type'       => $customer_type,
            'existingOrderItems'  => $this->orderService->buildOrderItemsHtml($order),
            'branches'            => $branches,
            // 'branches'            => $branchService->getAllBranches(),
            'categories'          => app(CategoryService::class)->getAllCategories(),
            'clients'             => app(ClientService::class)->getAllClients(),
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
        $order = $this->orderService->getOrderById($id);
        $this->authorize('cancel', $order);
        try {
            // 1. Buscamos el pedido antes de borrarlo para conocer su flujo (Venta o Compra)
            $order = $this->orderService->getOrderById($id);

            // 2. Determinamos la ruta de redirección basada en el tipo de cliente
            $redirectRoute = ($order->customer_type === 'App\Models\Branch')
                ? 'web.orders.purchases'
                : 'web.orders.index';

            // 3. Ejecutamos la eliminación
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
}
