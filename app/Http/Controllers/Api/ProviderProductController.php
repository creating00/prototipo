<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\ProviderProduct;
use App\Services\ProviderProductService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProviderProductController extends Controller
{
    protected ProviderProductService $providerProductService;

    public function __construct(ProviderProductService $providerProductService)
    {
        $this->providerProductService = $providerProductService;
    }

    /**
     * Obtener datos de un provider-product para edición
     */
    public function show(Provider $provider, ProviderProduct $providerProduct)
    {
        return response()->json([
            'provider_product' => $providerProduct->load('product'),
            'products' => $provider->products()->orderBy('name')->get(),
        ]);
    }

    /**
     * Actualizar provider-product
     */
    public function update(Request $request, Provider $provider, ProviderProduct $providerProduct)
    {
        try {
            $this->providerProductService->updateProviderProduct($providerProduct, $request->all());

            return response()->json([
                'message' => 'Producto actualizado correctamente',
                'provider_product' => $providerProduct->fresh()
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
