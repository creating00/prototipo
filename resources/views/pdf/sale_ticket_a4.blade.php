<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Recibo de Pago - {{ str_pad($sale->internal_number ?? $sale->id, 8, '0', STR_PAD_LEFT) }}</title>

    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            width: 100%;
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
        }

        .container {
            position: relative;
            padding: 10px;
        }

        /* Encabezado Estilo A4 */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .company-name {
            font-size: 22px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }

        .receipt-title {
            font-size: 18px;
            font-weight: bold;
            color: #666;
            text-align: right;
            margin: 0;
        }

        /* Tablas de Items y Pagos */
        table.main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table.main-table th {
            border-bottom: 1px dashed #000;
            padding: 8px 5px;
            text-align: left;
            background-color: #f9f9f9;
            text-transform: uppercase;
            font-size: 10px;
        }

        table.main-table td {
            padding: 8px 5px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row td {
            border-top: 1px dashed #000;
            border-bottom: none;
            padding-top: 10px;
            font-weight: bold;
            font-size: 12px;
        }

        /* Watermark */
        .watermark {
            position: fixed;
            top: 35%;
            left: 15%;
            width: 70%;
            font-size: 120px;
            color: rgba(0, 0, 0, 0.05);
            transform: rotate(-45deg);
            text-align: center;
            z-index: -1;
        }

        .info-box {
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 4px;
        }

        .info-box p {
            margin: 3px 0;
        }
    </style>
</head>

<body>
    {{-- WATERMARK --}}
    @if ($sale->remaining_balance <= 0)
        <div class="watermark">PAGADO</div>
    @endif

    <div class="container">
        <table class="header-table">
            <tr>
                <td style="width: 20%;">
                    @if ($sale->branch->logo_path)
                        <img src="{{ $sale->branch->logo_path }}" style="max-width:140px">
                    @endif
                </td>
                <td style="width: 50%; padding-left: 20px;">
                    <h1 class="company-name">{{ $sale->branch->name }}</h1>
                    <p style="margin: 5px 0;">{{ $sale->branch->address }}</p>
                    <p style="margin: 2px 0;">Tel: {{ $sale->branch->phone }}</p>
                </td>
                <td style="width: 30%; text-align: right;">
                    <h2 class="receipt-title">RECIBO DE PAGO</h2>
                    <p style="font-size: 14px; margin-top: 10px;">
                        <strong>Nº: {{ str_pad($sale->internal_number ?? $sale->id, 8, '0', STR_PAD_LEFT) }}</strong>
                    </p>
                </td>
            </tr>
        </table>

        <hr style="border-top: 1px dashed #000;">

        <table style="width: 100%; margin-top: 10px;">
            <tr>
                <td style="width: 50%;">
                    <p><strong>CLIENTE:</strong> {{ $sale->customer_name }}</p>
                    <p><strong>DOCUMENTO:</strong> {{ $sale->customer->document ?? 'N/A' }}</p>
                </td>
                <td style="width: 50%; text-align: right;">
                    <p><strong>FECHA:</strong> {{ $sale->created_at->format('d/m/Y H:i') }}</p>
                    <p><strong>VENDEDOR:</strong> {{ $sale->user->name }}</p>
                </td>
            </tr>
        </table>

        <table class="main-table">
            <thead>
                <tr>
                    <th style="width: 60%;">Concepto / Descripción</th>
                    <th class="text-right" style="width: 15%;">Cantidad</th>
                    <th class="text-right" style="width: 25%;">Importe</th>
                </tr>
            </thead>
            <tbody>
                @php $subtotal = 0; @endphp
                @foreach ($sale->items as $item)
                    @php $subtotal += $item->subtotal; @endphp
                    <tr>
                        <td>{{ $item->descriptionForReceipt($sale) }}</td>
                        <td class="text-right">{{ (int) $item->quantity }}</td>
                        <td class="text-right">
                            @if ($sale->sale_type === \App\Enums\SaleType::Repair)
                                {{ $loop->first ? '$ ' . number_format($sale->total_general_ars, 2, ',', '.') : '-' }}
                            @else
                                $ {{ number_format($item->subtotal, 2, ',', '.') }}
                            @endif
                        </td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2" class="text-right">SUBTOTAL</td>
                    <td class="text-right">
                        $
                        {{ number_format($sale->sale_type === \App\Enums\SaleType::Repair ? $sale->total_general_ars : $subtotal, 2, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top: 30px;">
            <h4 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">DETALLE DE PAGOS</h4>
            <table class="main-table">
                <thead>
                    <tr>
                        <th>Medio de Pago</th>
                        <th class="text-center">Moneda</th>
                        <th class="text-right">Monto Original</th>
                        <th class="text-right">Equivalente ARS</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalPagadoARS = 0; @endphp
                    @foreach ($sale->payments as $payment)
                        @php
                            $amountARS =
                                $payment->currency === \App\Enums\CurrencyType::ARS
                                    ? $payment->amount
                                    : $payment->amount * ($payment->exchange_rate ?? $sale->exchange_rate);
                            $totalPagadoARS += $amountARS;
                        @endphp
                        <tr>
                            <td>{{ $payment->payment_type->label() }}</td>
                            <td class="text-center">{{ $payment->currency->code() }}</td>
                            <td class="text-right">
                                {{ number_format($payment->amount, 2, ',', '.') }}
                                @if ($payment->currency !== \App\Enums\CurrencyType::ARS)
                                    <br><small>TC:
                                        {{ number_format($payment->exchange_rate ?? $sale->exchange_rate, 2, ',', '.') }}</small>
                                @endif
                            </td>
                            <td class="text-right">$ {{ number_format($amountARS, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="width: 40%; margin-left: 60%; margin-top: 20px;">
            <table style="width: 100%; border-collapse: collapse;">
                @if ($sale->discount_amount > 0)
                    <tr>
                        <td style="padding: 5px 0;">Descuento</td>
                        <td class="text-right" style="color: red;">- $
                            {{ number_format($sale->discount_amount, 2, ',', '.') }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="padding: 5px 0;"><strong>TOTAL VENTA</strong></td>
                    <td class="text-right"><strong>$
                            {{ number_format($sale->total_general_ars, 2, ',', '.') }}</strong></td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;">PAGADO</td>
                    <td class="text-right">$ {{ number_format($totalPagadoARS, 2, ',', '.') }}</td>
                </tr>
                @if ($sale->change_returned > 0)
                    <tr>
                        <td style="padding: 5px 0;">VUELTO</td>
                        <td class="text-right">$ {{ number_format($sale->change_returned, 2, ',', '.') }}</td>
                    </tr>
                @endif
                @if ($sale->remaining_balance > 0)
                    <tr style="color: red;">
                        <td style="padding: 5px 0;"><strong>SALDO PENDIENTE</strong></td>
                        <td class="text-right"><strong>$
                                {{ number_format($sale->remaining_balance, 2, ',', '.') }}</strong></td>
                    </tr>
                @endif
            </table>
        </div>

        @if ($sale->notes)
            <div class="info-box" style="margin-top: 20px;">
                <p><strong>Observaciones:</strong></p>
                <p>{{ $sale->notes }}</p>
            </div>
        @endif

        <div style="page-break-before: always;"></div>
        @include('pdf.partials.terms')
    </div>
</body>

</html>
