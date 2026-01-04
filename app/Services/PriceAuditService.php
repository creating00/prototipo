<?php

namespace App\Services;

use App\Models\PriceModification;
use Illuminate\Support\Facades\Auth;

class PriceAuditService
{
    /**
     * Registra una modificación de precio si el precio final difiere del original.
     */
    public function recordModification(array $data): void
    {
        $original = (float) $data['original_price'];
        $modified = (float) $data['modified_price'];

        if ($this->hasChanged($original, $modified)) {
            PriceModification::create([
                'branch_id'      => $data['branch_id'],
                'user_id'        => Auth::id() ?? $data['user_id'] ?? null,
                'product_id'     => $data['product_id'],
                'original_price' => $original,
                'modified_price' => $modified,
                'sale_id'        => $data['sale_id'] ?? null,
                'reason'         => $data['reason'] ?? 'Cambio manual en punto de venta',
            ]);
        }
    }

    /**
     * Compara precios usando una tolerancia para evitar errores de precisión.
     */
    private function hasChanged(float $original, float $modified): bool
    {
        // Consideramos cambio si la diferencia es mayor a 0.001
        return abs($original - $modified) > 0.0001;
    }
}
