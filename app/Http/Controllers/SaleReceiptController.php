<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;

class SaleReceiptController extends Controller
{
    /**
     * Ticket térmico (70/80mm)
     */
    public function ticket(Sale $sale)
    {
        $this->loadRelations($sale);

        return $this->makeTicketPdf($sale)
            ->stream("ticket_venta_{$sale->id}.pdf");
    }

    /**
     * Versión A4
     */
    public function a4(Sale $sale)
    {
        $this->loadRelations($sale);

        return $this->makeA4Pdf($sale)
            ->stream("venta_{$sale->id}.pdf");
    }

    /**
     * ===== Helpers privados =====
     */

    private function loadRelations(Sale $sale): void
    {
        $sale->load([
            'branch',
            'user',
            'customer',
            'items.product',
            'payments.user',
        ]);
    }

    private function makeTicketPdf(Sale $sale)
    {
        return Pdf::loadView('pdf.sale_ticket', compact('sale'))
            ->setPaper([0, 0, 226.77, 600]); // 80mm
    }

    private function makeA4Pdf(Sale $sale)
    {
        return Pdf::loadView('pdf.sale_ticket_a4', compact('sale'))
            ->setPaper('A4');
    }
}
