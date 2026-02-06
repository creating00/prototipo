<?php

namespace App\Services\Sale\Traits;

trait NormalizesSaleInput
{
    /**
     * Resuelve el monto recibido y ajusta los totales si es un descuento manual.
     */
    protected function resolveAndAdjustTotals(array $data, array &$totals): float
    {
        // Forzamos que los valores sean numéricos antes de sumar
        $totalSum = (float) array_sum(array_map('floatval', $totals));

        // Obtenemos el valor crudo
        $inputAmount = $data['amount_received'] ?? null;

        // Caso 1: Pago completo
        // Ahora entra si es null, si es 0, o si es la cadena "0.00"
        if (is_null($inputAmount) || (float)$inputAmount === 0.0) {
            return $totalSum;
        }

        $inputAmount = (float) $inputAmount;

        // Caso 2: Salida rápida / Descuento manual
        if ($inputAmount > 0 && $inputAmount < $totalSum) {
            $currencyKey = array_key_first($totals);
            if ($currencyKey) {
                $totals[$currencyKey] = $inputAmount;
            }
        }

        return $inputAmount;
    }
}
