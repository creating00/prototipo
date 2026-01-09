<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\BaseProviderController;
use App\Models\Product;
use App\Models\Provider;
use App\Services\ProviderProductService;
use App\Services\ProviderService;
use Illuminate\Http\Request;

class ProviderWebController extends BaseProviderController
{
    protected ProviderProductService $providerProductService;
    public function __construct(
        ProviderService $providerService,
        ProviderProductService $providerProductService
    ) {
        parent::__construct($providerService);
        $this->providerProductService = $providerProductService;
    }

    public function index()
    {
        $this->authorize('viewAny', Provider::class);
        $rowData = $this->providerService->getAllProvidersForDataTable();
        $providers = $this->providerService->getAllProviders();

        $headers = [
            '#',
            'Razón Social',
            'CUIT',
            // 'Alias',
            'Contacto',
            // 'Email',
            'Teléfono',
            'Dirección',
            'Creado en'
        ];
        $hiddenFields = ['id'];

        return view('admin.provider.index', compact('providers', 'rowData', 'headers', 'hiddenFields'));
    }

    public function show($id)
    {
        $provider = $this->providerService->getProviderById($id);
        $this->authorize('view', $provider);

        $products = Product::orderBy('name')->get();

        $providerProducts = $this->providerProductService
            ->getProductsForProvider($provider->id);

        $headers = [
            '#',
            'Producto',
            'Código Proveedor',
            'Lead Time',
            'Estado',
            'Precio vigente',
        ];

        $hiddenFields = ['id'];

        $rowData = $providerProducts->map(function ($pp, $index) {
            return [
                'id' => $pp->id,
                'number' => $index + 1,
                'product' => $pp->product->name ?? '—',
                'provider_code' => $pp->provider_code ?? '—',
                'lead_time_days' => $pp->lead_time_days ?? '—',
                'status' => $pp->status->label(),
                'price' => $pp->currentPrice
                    ? $pp->currentPrice->currency->symbol() . ' ' .
                    number_format($pp->currentPrice->cost_price, 2)
                    : '—',
            ];
        })->toArray();

        return view('admin.provider.show', compact(
            'provider',
            'products',
            'headers',
            'rowData',
            'hiddenFields'
        ));
    }

    public function create()
    {
        $this->authorize('create', Provider::class);
        return view('admin.provider.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Provider::class);
        try {
            $this->providerService->createProvider($request->all());
            return redirect()->route('web.providers.index')
                ->with('success', 'Provider created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        }
    }

    public function edit($id)
    {
        $provider = $this->providerService->getProviderById($id);
        $this->authorize('update', $provider);

        return view('admin.provider.edit', compact('provider'));
    }

    public function update(Request $request, $id)
    {
        $provider = $this->providerService->getProviderById($id);
        $this->authorize('update', $provider);
        try {
            $this->providerService->updateProvider($id, $request->all());
            return redirect()->route('web.providers.index')
                ->with('success', 'Provider updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $provider = $this->providerService->getProviderById($id);
        $this->authorize('delete', $provider);
        try {
            $this->providerService->deleteProvider($id);
            return redirect()->route('web.providers.index')
                ->with('success', 'Provider deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
