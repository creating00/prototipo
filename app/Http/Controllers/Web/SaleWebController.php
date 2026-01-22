<?php

namespace App\Http\Controllers\Web;

use App\Enums\RepairType;
use App\Enums\SaleStatus;
use App\Enums\SaleType;
use App\Http\Controllers\BaseSaleController;
use App\Models\Sale;
use App\Traits\AuthTrait;
use Illuminate\Http\Request;

class SaleWebController extends BaseSaleController
{
    use AuthTrait;

    public function index()
    {
        if ($redirect = $this->redirectIfNotAdmin('web.sales.create-client')) {
            return $redirect;
        }

        $this->authorize('viewAny', Sale::class);

        $rowData = $this->saleService->getAllSalesForDataTable();
        $sales = $this->saleService->getAllSales();

        $headers = ['#', 'Sucursal', 'Cliente', 'Tipo', 'Pago', 'Total', 'Facturación', 'Estado', 'Creado en:'];
        $hiddenFields = [
            'id',
            'status_raw',
            'phone',
            'whatsapp-url',
            'customer_type',
            'totals_json',
            'customer_name_raw'
        ];

        return view('admin.sales.index', compact('sales', 'rowData', 'headers', 'hiddenFields'));
    }

    public function show($id)
    {
        $sale = $this->saleService->getSaleById($id);
        $this->authorize('view', $sale);

        $itemsData = $this->saleService->getSaleItemsData($sale);

        return view('admin.sales.details', [
            'sale'         => $sale,
            'backUrl'      => route('web.sales.index'),
            'rowData'      => $itemsData['rowData'],
            'headers'      => $itemsData['headers'],
            'hiddenFields' => $itemsData['hiddenFields'],
        ]);
    }

    private function getCommonFormData(string $customerType = 'App\Models\Client', $sale = null): array
    {
        $branchService = app(\App\Services\BranchService::class);
        $categoryService = app(\App\Services\CategoryService::class);
        $discountService = app(\App\Services\DiscountService::class);
        $repairAmountService = app(\App\Services\RepairAmountService::class);
        $userBranchId = $this->currentBranchId();

        $activeRepairAmounts = \App\Models\RepairAmount::query()
            ->forBranch($userBranchId)
            ->active()
            ->get()
            ->pluck('amount', 'repair_type.value') // [type_id => amount]
            ->toArray();

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
            'categories'      => $categoryService->getAllCategories(),
            'statusOptions'   => SaleStatus::forSelect(),
            'saleTypeOptions' => SaleType::forSelect(),
            'repairTypes'     => RepairType::forSelect(),
            'repairAmountsMap' => $activeRepairAmounts,
            'saleDate'        => $sale
                ? \Carbon\Carbon::parse($sale->sale_date)->format('Y-m-d')
                : now()->format('Y-m-d'),
            'paymentOptions'  => $paymentOptions,
            'discountOptions' => $discountService->getForSelect(),
            'discountMap'     => $discountService->getValueMap(),
        ];

        if ($customerType === 'App\Models\Client') {
            $clientService = app(\App\Services\ClientService::class);

            $originBranch = $branchService->getUserBranch($userBranchId);
            if (!$originBranch) {
                abort(404, 'Sucursal de origen no encontrada.');
            }

            $data['branches'] = collect([$originBranch]);

            $data['clients'] = $clientService->getAllClients();

            $defaultDoc = config('app.default_client_document');
            $data['defaultClientId'] = $data['clients']
                ->where('document', $defaultDoc)
                ->first()?->id;
        } else {
            $originBranch = $branchService->getUserBranch($userBranchId);

            if (!$originBranch) {
                abort(404, 'Sucursal de origen no encontrada.');
            }

            $data['originBranch'] = $originBranch;
            $data['branches'] = $branchService->getAllBranches();
            $data['destinationBranches'] = $branchService->getAllBranchesExcept($userBranchId);
        }

        return $data;
    }

    private function getCreateRoute(string $customerType): string
    {
        return $customerType === 'App\Models\Branch'
            ? route('web.sales.create-branch')
            : route('web.sales.create-client');
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
        $this->authorize('createClient', Sale::class);

        return $this->create(new Request(['type' => 'client']));
    }

    public function createBranch()
    {
        $this->authorize('createBranch', Sale::class);

        return $this->create(new Request(['type' => 'branch']));
    }

    public function store(Request $request)
    {
        if ($request->input('customer_type') === 'App\Models\Branch') {
            $this->authorize('createBranch', Sale::class);
        } else {
            $this->authorize('createClient', Sale::class);
        }

        //dd($request->request);

        try {
            $sale = $this->saleService->createSale($request->all());

            $receiptType = $request->input('receipt_type');

            if ($receiptType) {
                session()->flash('print_receipt', [
                    'type' => $receiptType,
                    'sale_id' => $sale->id,
                ]);
            }

            return redirect($this->getCreateRoute($request->input('customer_type')))
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
        $this->authorize('update', $sale);

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
        $sale = $this->saleService->getSaleById($id);

        $this->authorize('update', $sale);

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
        $sale = $this->saleService->getSaleById($id);

        $this->authorize('destroy', $sale);

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
