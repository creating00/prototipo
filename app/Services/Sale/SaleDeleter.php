<?php

namespace App\Services\Sale;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class SaleDeleter
{
    protected SaleItemProcessor $itemProcessor;

    public function __construct(SaleItemProcessor $itemProcessor)
    {
        $this->itemProcessor = $itemProcessor;
    }

    public function delete(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            // Eliminar pagos
            $sale->payments()->delete();

            $this->itemProcessor->releaseStock($sale);
            $sale->items()->delete();
            $sale->delete();
        });
    }
}
