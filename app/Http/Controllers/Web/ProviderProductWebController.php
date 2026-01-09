<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Provider;
use App\Models\ProviderProduct;
use App\Services\ProviderProductService;
use App\Services\ProductProviderPriceService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProviderProductWebController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        protected ProviderProductService $providerProductService
    ) {}

    public function store(Request $request, Provider $provider)
    {
        $this->authorize('create', ProviderProduct::class);
        $data = $request->all();
        $data['provider_id'] = $provider->id;

        $this->providerProductService->attachProductToProvider($data);

        return redirect()
            ->route('web.providers.show', $provider)
            ->with('success', 'Producto asociado correctamente');
    }

    public function edit(Provider $provider, ProviderProduct $providerProduct)
    {
        $this->authorize('update', $providerProduct);
        $products = Product::orderBy('name')->get();

        return view('admin.provider.partials.provider-product._form', [
            'provider' => $provider,
            'providerProduct' => $providerProduct,
            'products' => $products,
        ]);
    }

    public function update(Request $request, Provider $provider, ProviderProduct $providerProduct)
    {
        $this->authorize('update', $providerProduct);
        $updated = $this->providerProductService->updateProviderProduct($providerProduct, $request->all());

        // Retornar JSON para AJAX
        return response()->json([
            'message' => 'Producto actualizado correctamente',
            'providerProduct' => $updated,
        ]);
    }

    public function prices(Provider $provider, ProviderProduct $providerProduct)
    {
        $this->authorize('view', $providerProduct);
        $prices = $providerProduct->prices()->latest('effective_date')->get();

        return view('admin.provider.partials.provider-product._prices-form', [
            'providerProduct' => $providerProduct,
            'prices' => $prices,
        ]);
    }

    public function storePrice(Request $request, Provider $provider, ProviderProduct $providerProduct, ProductProviderPriceService $service)
    {
        $this->authorize('update', $providerProduct);
        $data = $request->validate([
            'provider_product_id' => 'required|exists:provider_products,id',
            'cost_price' => 'required|numeric|min:0',
            'currency' => 'required|string',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
        ]);

        $price = $service->createPrice($data);

        return response()->json([
            'message' => 'Precio agregado correctamente',
            'price' => $price,
        ]);
    }
}
