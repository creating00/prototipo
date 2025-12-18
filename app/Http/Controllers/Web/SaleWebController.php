<?php

namespace App\Http\Controllers\Web;

use App\Enums\SaleStatus;
use App\Enums\SaleType;
use App\Http\Controllers\BaseSaleController;
use App\Traits\AuthTrait;
use Illuminate\Http\Request;

class SaleWebController extends BaseSaleController
{
    use AuthTrait;
    public function index()
    {
        $rowData = $this->saleService->getAllSalesForDataTable();
        $sales = $this->saleService->getAllSales();

        $headers = ['#', 'Sucursal', 'Cliente', 'Total', 'Estado', 'Creado en:'];
        $hiddenFields = ['id'];

        return view('admin.sales.index', compact('sales', 'rowData', 'headers', 'hiddenFields'));
    }

    public function create()
    {
        // Crea una venta por defecto a un Cliente
        $branches = app(\App\Services\BranchService::class)->getAllBranches();
        $categories = app(\App\Services\CategoryService::class)->getAllCategories();
        $clients = app(\App\Services\ClientService::class)->getAllClients();
        $statusOptions = SaleStatus::forSelect();

        $customer_type = 'App\Models\Client';

        return view('admin.sales.create-client', compact(
            'branches',
            'categories',
            'clients',
            'statusOptions',
            'customer_type',
        ));
    }

    public function createClient()
    {
        $branches = app(\App\Services\BranchService::class)->getAllBranches();
        $categories = app(\App\Services\CategoryService::class)->getAllCategories();
        $clients = app(\App\Services\ClientService::class)->getAllClients();
        $statusOptions = SaleStatus::forSelect();
        $saleTypeOptions = SaleType::forSelect();

        $customer_type = 'App\Models\Client';

        $saleDate = now()->format('Y-m-d');

        $paymentOptions = [
            \App\Enums\PaymentType::Cash->value => \App\Enums\PaymentType::Cash->label(),
            \App\Enums\PaymentType::Transfer->value => \App\Enums\PaymentType::Transfer->label(),
        ];

        return view('admin.sales.create-client', compact(
            'customer_type',
            'branches',
            'categories',
            'clients',
            'statusOptions',
            'saleTypeOptions',
            'saleDate',
            'paymentOptions'
        ));
    }

    public function createBranch()
    {
        $userBranchId = $this->currentBranchId();

        $branchService = app(\App\Services\BranchService::class);

        $originBranch = $branchService->getUserBranch($userBranchId);
        $destinationBranches = $branchService->getAllBranchesExcept($userBranchId);

        $categories = app(\App\Services\CategoryService::class)->getAllCategories();
        $statusOptions = SaleStatus::forSelect();

        $customer_type = 'App\Models\Branch';

        return view('admin.sales.create-branch', compact(
            'originBranch',
            'destinationBranches',
            'categories',
            'statusOptions',
            'customer_type',
        ));
    }

    public function store(Request $request)
    {
        try {
            $this->saleService->createSale($request->all());

            return redirect()
                ->route('web.sales.index')
                ->with('success', 'Venta creada exitosamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->validator)
                ->withInput();
        }
    }

    public function edit($id)
    {
        $sale = $this->saleService->getSaleById($id);

        $branches = app(\App\Services\BranchService::class)->getAllBranches();
        $categories = app(\App\Services\CategoryService::class)->getAllCategories();
        $clients = app(\App\Services\ClientService::class)->getAllClients();

        $statusOptions = SaleStatus::forSelect();

        return view('admin.sales.edit', compact(
            'sale',
            'branches',
            'categories',
            'clients',
            'statusOptions'
        ));
    }

    public function update(Request $request, $id)
    {
        try {
            $this->saleService->updateSale($id, $request->all());

            return redirect()
                ->route('web.sales.index')
                ->with('success', 'Venta actualizada exitosamente');
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
            $this->saleService->deleteSale($id);

            return redirect()
                ->route('web.sales.index')
                ->with('success', 'Venta eliminada exitosamente');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }
}
