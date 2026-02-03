<?php

namespace App\Traits;

trait CanHandleSalePrints
{
    /**
     * Flashea los datos de impresión a la sesión.
     */
    protected function triggerPrint($saleId, $type = 'ticket'): void
    {
        if (!$saleId || !$type) return;

        session()->flash('print_receipt', [
            'sale_id' => $saleId,
            'type'    => $type
        ]);
    }
}
