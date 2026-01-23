<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Enums\SaleType;
use App\Enums\RepairType;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function generate($paymentId)
    {
        // Carga relaciones segun modelos proporcionados
        $payment = Payment::with([
            'paymentable.items.product.category',
            'paymentable.branch',
            'paymentable.user'
        ])->findOrFail($paymentId);

        $sale = $payment->paymentable;
        $client = $sale->customer;
        $branch = $sale->branch;

        $invoice = [
            'number'   => 'INV-' . now()->format('Y') . '-' . $payment->id,
            'date'     => $payment->created_at->format('d/m/Y'),
            'due_date' => $payment->created_at->addDays(30)->format('d/m/Y'),
            'status'   => 'Pagada',
            'type'     => $sale->sale_type->label(),
        ];

        $company = [
            'name'    => 'TECNONAUTA',
            'address' => $branch ? "{$branch->address} - {$branch->name}" : 'Dirección no disponible',
            'city'    => '---',
            'phone'   => '000',
            'email'   => 'example@mail.com',
        ];

        $clientData = [
            'name'    => $sale->customer_name,
            'address' => $client->address ?? '',
            'phone'   => $client->phone ?? '',
            'tax_id'  => $client->document ?? '',
        ];

        $items = $sale->items->map(function ($item) use ($sale) {
            return [
                'description' => $this->resolveItemDescription($sale, $item),
                'quantity'    => $item->quantity,
                'unit'        => '',
                'price'       => $item->unit_price,
                'total'       => $item->subtotal,
            ];
        })->toArray();


        $totals = [
            'subtotal' => $sale->getTotalGeneralArs(),
            'tax_rate' => 0,
            'tax'      => 0,
            'total'    => $sale->getTotalGeneralArs(),
        ];

        return Pdf::loadView('invoices.invoice', [
            'invoice' => $invoice,
            'company' => $company,
            'client'  => $clientData,
            'items'   => $items,
            'totals'  => $totals,
        ])->stream('factura-' . $invoice['number'] . '.pdf');
    }

    private function resolveItemDescription($sale, $item): string
    {
        $description = $item->product->name;

        if ($sale->sale_type === SaleType::Repair) {
            $repairLabel = $this->getRepairLabel($item->product->category_id);
            $description = "[{$repairLabel}] {$description}";
        }

        return $description;
    }


    /**
     * Busca el label del Enum RepairType segun el ID de categoria.
     */
    private function getRepairLabel(?int $categoryId): string
    {
        if (!$categoryId) return 'Reparación';

        foreach (RepairType::cases() as $repair) {
            if ($repair->categoryId() === $categoryId) {
                return $repair->label();
            }
        }

        return 'Reparación General';
    }
}
