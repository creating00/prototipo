<?php

namespace App\Http\Controllers\Web;

use App\Enums\OrderStatus;
use App\Http\Controllers\BaseOrderController;
use App\Services\BranchService;
use App\Services\CategoryService;
use App\Services\ClientService;
use App\Traits\AuthTrait;
use Illuminate\Http\Request;

class OrderWebController extends BaseOrderController
{
    use AuthTrait;

    public function index()
    {
        $rowData = $this->orderService->getAllOrdersForDataTable();
        $orders = $this->orderService->getAllOrders();

        $headers = ['#', 'Sucursal', 'Cliente', 'Total', 'Estado', 'Creado en:'];
        $hiddenFields = ['id', 'status_raw', 'phone', 'whatsapp-url', 'customer_type'];

        return view('admin.order.index', compact('orders', 'rowData', 'headers', 'hiddenFields'));
    }

    public function purchaseDetails($id)
    {
        // 1. Obtenemos el pedido
        $order = $this->orderService->getOrderById($id);

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

        // El Service ya configuró: ['#', 'Producto', 'Cantidad', 'Precio Unitario', 'Subtotal']
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
        $rowData = $this->orderService->getPurchasedOrdersForDataTable();

        // Añadimos 'Fecha Recepción' y 'Recibido por'
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
        $branches = app(\App\Services\BranchService::class)->getAllBranches();
        $categories = app(\App\Services\CategoryService::class)->getAllCategories();
        $clients = app(\App\Services\ClientService::class)->getAllClients();
        $statusOptions = OrderStatus::forSale();

        $customer_type = 'App\Models\Client';

        return view('admin.order.create-client', compact(
            'customer_type',
            'branches',
            'categories',
            'clients',
            'statusOptions'
        ));
    }

    public function createBranch()
    {
        $userBranchId = $this->currentBranchId();

        $branchService = app(\App\Services\BranchService::class);

        // Sucursal origen (solo 1, pero en colección para no romper los selects)
        $originBranch = $branchService->getUserBranch($userBranchId);

        // Sucursales destino (todas menos la del usuario)
        $destinationBranches = $branchService->getAllBranchesExcept($userBranchId);

        $categories = app(\App\Services\CategoryService::class)->getAllCategories();
        $statusOptions = OrderStatus::forInternalOrder();

        return view('admin.order.create-branch', compact(
            'originBranch',
            'destinationBranches',
            'categories',
            'statusOptions'
        ));
    }

    public function store(Request $request)
    {
        try {
            $order = $this->orderService->createOrder($request->all());

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

        return view('admin.order.edit', [
            'order'               => $order,
            'existingOrderItems'  => $this->orderService->buildOrderItemsHtml($order),
            'branches'            => app(BranchService::class)->getAllBranches(),
            'categories'          => app(CategoryService::class)->getAllCategories(),
            'clients'             => app(ClientService::class)->getAllClients(),
            'statusOptions'       => OrderStatus::forSelect(),
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $order = $this->orderService->updateOrder($id, $request->all());

            // Redirigir según el tipo de cliente del pedido actualizado
            $route = ($order->customer_type === 'App\Models\Branch')
                ? 'web.orders.purchases'
                : 'web.orders.index';

            return redirect()->route($route)->with('success', 'Orden actualizada.');
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
