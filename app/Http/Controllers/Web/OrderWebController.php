<?php

namespace App\Http\Controllers\Web;

use App\Enums\OrderStatus;
use App\Http\Controllers\BaseOrderController;
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
        $hiddenFields = ['id'];

        return view('admin.order.index', compact('orders', 'rowData', 'headers', 'hiddenFields'));
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
        $statusOptions = OrderStatus::forSelect();

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
        $statusOptions = OrderStatus::forSelect();

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
            $this->orderService->createOrder($request->all());

            return redirect()
                ->route('web.orders.index')
                ->with('success', 'Orden creada exitosamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->validator)
                ->withInput();
        }
    }

    public function edit($id)
    {
        $order = $this->orderService->getOrderById($id);

        // Datos auxiliares, igual que en create
        $branches = app(\App\Services\BranchService::class)->getAllBranches();
        $categories = app(\App\Services\CategoryService::class)->getAllCategories();
        $clients = app(\App\Services\ClientService::class)->getAllClients();
        $statusOptions = OrderStatus::forSelect();

        return view('admin.order.edit', compact(
            'order',
            'branches',
            'categories',
            'clients',
            'statusOptions'
        ));
    }

    public function update(Request $request, $id)
    {
        try {
            $this->orderService->updateOrder($id, $request->all());

            return redirect()
                ->route('web.orders.index')
                ->with('success', 'Orden actualizada exitosamente');
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
            $this->orderService->deleteOrder($id);

            return redirect()
                ->route('web.orders.index')
                ->with('success', 'Orden eliminada exitosamente');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }
}
