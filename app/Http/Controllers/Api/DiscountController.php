<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Discount\DiscountApiRequest;
use App\Models\Discount;
use App\Services\DiscountService;
use Illuminate\Http\JsonResponse;

class DiscountController extends Controller
{
    public function __construct(
        protected DiscountService $discountService
    ) {}

    /**
     * Retorna los descuentos activos para usar en el checkout/venta
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->discountService->getActiveForSale(),
            'map' => $this->discountService->getValueMap()
        ]);
    }

    public function store(DiscountApiRequest $request): JsonResponse
    {
        $discount = $this->discountService->create($request->validated());

        return response()->json([
            'message' => 'Descuento creado con éxito',
            'data' => $discount
        ], 201);
    }

    public function show(Discount $discount): JsonResponse
    {
        return response()->json(['data' => $discount]);
    }

    public function update(DiscountApiRequest $request, Discount $discount): JsonResponse
    {
        $this->discountService->update($discount, $request->validated());

        return response()->json([
            'message' => 'Descuento actualizado con éxito',
            'data' => $discount->fresh()
        ]);
    }

    public function destroy(Discount $discount): JsonResponse
    {
        $this->discountService->delete($discount);
        return response()->json(['message' => 'Descuento eliminado']);
    }
}
