<?php

namespace App\Http\Controllers\Web;

use App\Enums\RepairType;
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
        $hiddenFields = ['id', 'status_raw', 'phone', 'whatsapp-url', 'customer_type'];

        return view('admin.sales.index', compact('sales', 'rowData', 'headers', 'hiddenFields'));
    }

    private function getCommonFormData(string $customerType = 'App\Models\Client', $sale = null): array
    {
        $branchService = app(\App\Services\BranchService::class);
        $categoryService = app(\App\Services\CategoryService::class);
        $discountService = app(\App\Services\DiscountService::class);

        // Opciones base de pago
        $paymentOptions = [
            \App\Enums\PaymentType::Cash->value => \App\Enums\PaymentType::Cash->label(),
            \App\Enums\PaymentType::Transfer->value => \App\Enums\PaymentType::Transfer->label(),
        ];

        // Si es sucursal, restringimos solo a Transferencia
        if ($customerType === 'App\Models\Branch') {
            $paymentOptions = [
                \App\Enums\PaymentType::Transfer->value => \App\Enums\PaymentType::Transfer->label(),
            ];
        }

        $data = [
            'customer_type'   => $customerType,
            'branches'        => $branchService->getAllBranches(),
            'categories'      => $categoryService->getAllCategories(),
            'statusOptions'   => SaleStatus::forSelect(),
            'saleTypeOptions' => SaleType::forSelect(),
            'repairTypes'     => RepairType::forSelect(),
            'saleDate'        => $sale ? \Carbon\Carbon::parse($sale->sale_date)->format('Y-m-d') : now()->format('Y-m-d'),
            'paymentOptions'  => $paymentOptions,
            'discountOptions' => $discountService->getForSelect(),
            'discountMap'     => $discountService->getValueMap(),
        ];

        if ($customerType === 'App\Models\Client') {
            $clientService = app(\App\Services\ClientService::class);
            $data['clients'] = $clientService->getAllClients();
            $defaultDoc = config('app.default_client_document');
            $data['defaultClientId'] = $data['clients']->where('document', $defaultDoc)->first()?->id;
        } else {
            $userBranchId = $this->currentBranchId();
            $originBranch = $branchService->getUserBranch($userBranchId);

            if (!$originBranch) {
                abort(404, 'Sucursal de origen no encontrada.');
            }

            $data['originBranch'] = $originBranch;
            $data['destinationBranches'] = $branchService->getAllBranchesExcept($userBranchId);
        }

        return $data;
    }

    public function create(Request $request)
    {
        $typeParam = $request->get('type');
        $type = $typeParam === 'branch' ? 'App\Models\Branch' : 'App\Models\Client';

        $view = $type === 'App\Models\Branch' ? 'admin.sales.create-branch' : 'admin.sales.create-client';

        return view($view, $this->getCommonFormData($type));
    }

    public function createClient()
    {
        return $this->create(new Request(['type' => 'client']));
    }

    public function createBranch()
    {
        return $this->create(new Request(['type' => 'branch']));
    }

    public function store(Request $request)
    {
        //dd($request->all());
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
        $existingOrderItems = $this->saleService->buildOrderItemsHtml($sale);
        $customerType = $sale->customer_type;

        return view('admin.sales.edit', array_merge(
            $this->getCommonFormData($customerType, $sale),
            compact('sale', 'existingOrderItems')
        ));
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
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
