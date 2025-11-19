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
            'order.product.branch',
            'order.client',
            'order.user'
        ])->findOrFail($paymentId);

        $order   = $payment->order;
        $client  = $order->client;
        $product = $order->product;
        $branch  = $product->branch;

        // Datos de factura
        $invoice = [
            'number'   => 'INV-' . now()->format('Y') . '-' . $payment->id,
            'date'     => $payment->created_at->format('d/m/Y'),
            'due_date' => $payment->created_at->addDays(30)->format('d/m/Y'),
            'status'   => 'Pagada',
        ];

        // Datos de la empresa (Sucursal)
        $company = [
            'name'    => $branch->name,
            'address' => $branch->address,
            'city'    => 'AAA',
            'phone'   => '333',
            'email'   => '',
            'tax_id'  => '131',
        ];

        // Datos del cliente
        $clientData = [
            'name'    => $client->first_name . ' ' . $client->last_name,
            'address' => '',
            'city'    => '',
            'phone'   => '',
            'email'   => '',
            'tax_id'  => $client->document,
        ];

        // Ítem único basado en amount_to_charge
        $totalItem = $order->amount_to_charge;
        $unitPrice = $order->quantity > 0
            ? $totalItem / $order->quantity
            : $totalItem;

        $items = [
            [
                'description' => $product->name,
                'quantity'    => $order->quantity,
                'unit'        => '',
                'price'       => $unitPrice,
                'total'       => $totalItem,
            ],
        ];

        // Totales
        $subtotal = $totalItem;
        $tax_rate = 21;
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
