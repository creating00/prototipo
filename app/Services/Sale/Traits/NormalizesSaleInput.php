<?php

namespace App\Services\Sale\Traits;

trait NormalizesSaleInput
{
    /**
     * Resuelve el monto recibido y ajusta los totales si es un descuento manual.
     */
    protected function resolveAndAdjustTotals(array $data, array &$totals): float
    {
        // 1. Suma de lo que viene en el JSON de totales
        $totalSum = (float) array_sum(array_map('floatval', $totals));

        // 2. Tomamos el monto del primer pago (Legacy field)
        $inputAmount = $data['amount_received'] ?? null;

        // Caso 1: Si no hay monto recibido o es 0, asumimos pago exacto
        if (is_null($inputAmount) || (float)$inputAmount === 0.0) {
            return $totalSum;
        }

        $inputAmount = (float) $inputAmount;

        // Caso 2: Ajuste de total (Descuento manual)
        // Solo ajustamos si el monto recibido es MENOR al total 
        // Y NO es una venta dual (en dual la suma de ambos pagos debe dar el total)
        $isDual = isset($data['enable_dual_payment']) && (int)$data['enable_dual_payment'] === 1;

        if (!$isDual && $inputAmount > 0 && $inputAmount < ($totalSum - 0.01)) {
            $currencyKey = array_key_first($totals);
            if ($currencyKey) {
                // Solo sobreescribimos si el usuario realmente cambió el monto a pagar
                $totals[$currencyKey] = $inputAmount;
            }
        }

        return $inputAmount;
    }
}
