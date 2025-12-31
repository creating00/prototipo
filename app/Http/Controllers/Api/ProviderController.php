<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseProviderController;
use App\Models\Provider;
use Illuminate\Http\Request;

class ProviderController extends BaseProviderController
{
    public function index()
    {
        $providers = $this->providerService->getAllProviders();
        return response()->json($providers);
    }

    public function store(Request $request)
    {
        try {
            $provider = $this->providerService->createProvider($request->all());
            return response()->json($provider, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function show($id)
    {
        $provider = $this->providerService->getProviderById($id);
        return response()->json($provider);
    }

    public function update(Request $request, $id)
    {
        try {
            $provider = $this->providerService->updateProvider($id, $request->all());
            return response()->json($provider);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function getProducts(Provider $provider)
    {
        try {
            $products = $this->providerService->getProviderProducts($provider->id);
            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar productos'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $result = $this->providerService->deleteProvider($id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }
}
