<?php

namespace App\Services;

use App\Models\ProductProviderPrice;
use App\Traits\AuthTrait;
use Illuminate\Validation\ValidationException;

class ProductProviderPriceService
{
    use AuthTrait;

    public function createPrice(array $data): ProductProviderPrice
    {
        // El branch_id se asignará automáticamente vía el Trait del Modelo

        if ($this->hasOverlappingPrice(
            $data['provider_product_id'],
            $data['effective_date'],
            $data['end_date'] ?? null
        )) {
            throw ValidationException::withMessages([
                'effective_date' => 'Existe un precio que se solapa con el rango indicado en esta sucursal.',
            ]);
        }

        // Cerrar precio vigente anterior en la sucursal actual
        ProductProviderPrice::where('provider_product_id', $data['provider_product_id'])
            ->current()
            ->update([
                'end_date' => now()->subDay(),
            ]);

        return ProductProviderPrice::create($data);
    }

    public function hasOverlappingPrice(
        int $providerProductId,
        string $effectiveDate,
        ?string $endDate = null,
        ?int $ignoreId = null
    ): bool {
        // El Global Scope ya limita la búsqueda a la sucursal del usuario
        return ProductProviderPrice::where('provider_product_id', $providerProductId)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($effectiveDate, $endDate) {
                $q->where('effective_date', '<=', $endDate ?? '9999-12-31')
                    ->where(function ($q2) use ($effectiveDate) {
                        $q2->whereNull('end_date')
                            ->orWhere('end_date', '>=', $effectiveDate);
                    });
            })
            ->exists();
    }

    public function getCurrentPrice(int $providerProductId): ?ProductProviderPrice
    {
        return ProductProviderPrice::where('provider_product_id', $providerProductId)
            ->current()
            ->latest('effective_date')
            ->first();
    }
}
