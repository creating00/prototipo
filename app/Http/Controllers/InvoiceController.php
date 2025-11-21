<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function generate($paymentId)
    {
        $payment = Payment::with([
            'order.items.product.branch',
            'order.client',
            'order.user'
        ])->findOrFail($paymentId);

        $order   = $payment->order;
        $client  = $order->client;
        $items   = $order->items;

        $firstItem = $items->first();
        $branch = $firstItem ? $firstItem->product->branch : null;

        // Datos de factura
        $invoice = [
            'number'   => 'INV-' . now()->format('Y') . '-' . $payment->id,
            'date'     => $payment->created_at->format('d/m/Y'),
            'due_date' => $payment->created_at->addDays(30)->format('d/m/Y'),
            'status'   => 'Pagada',
        ];

        // Datos de la empresa (Sucursal)
        $company = [
            'name'    => 'TECNONAUTA',
            'address' => $branch ? ($branch->address . ' - ' . $branch->name) : 'Dirección no disponible',
            'city'    => '---',
            'phone'   => '000',
            'email'   => 'example@mail.com',
            'tax_id'  => '',
        ];

        // Datos del cliente
        $clientData = [
            'name'    => $client->full_name,
            'address' => $client->address,
            'city'    => '',
            'phone'   => $client->phone,
            'email'   => '',
            'tax_id'  => $client->document,
        ];

        // Múltiples ítems basados en los productos de la orden
        $items = $order->items->map(function ($item) {
            return [
                'description' => $item->product->name,
                'quantity'    => $item->quantity,
                'unit'        => '',
                'price'       => $item->unit_price,
                'total'       => $item->subtotal,
            ];
        })->toArray();

        // Calcular totales
        $subtotal = $order->total_amount;
        $tax_rate = 0;
        $tax      = $subtotal * ($tax_rate / 100);
        $total    = $subtotal + $tax;

        $totals = [
            'subtotal' => $subtotal,
            'tax_rate' => $tax_rate,
            'tax'      => $tax,
            'total'    => $total,
        ];

        return Pdf::loadView('invoices.invoice', [
            'invoice' => $invoice,
            'company' => $company,
            'client'  => $clientData,
            'items'   => $items,
            'totals'  => $totals,
        ])->stream('factura-' . $invoice['number'] . '.pdf');
    }
}
