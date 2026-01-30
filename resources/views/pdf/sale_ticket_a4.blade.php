<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Recibo de Pago</title>

    <style>
        @page {
            size: A4;
            margin: 15mm;
        }

        body {
            width: 100%;
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
        }

        .container {
            position: relative;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 6px;
            border-bottom: 1px solid #ccc;
        }

        th {
            background: #f2f2f2;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .total-row td {
            font-weight: bold;
            border-top: 2px solid #000;
        }

        .watermark {
            position: fixed;
            top: 40%;
            left: 25%;
            font-size: 120px;
            color: rgba(0, 0, 0, .08);
            transform: rotate(-45deg);
            z-index: 0;
        }
    </style>

    <style>
        @font-face {
            font-family: 'DejaVu Sans Mono';
            src: url('{{ public_path('fonts/DejaVuSansMono.ttf') }}') format('truetype');
        }
    </style>
</head>

<body>
    {{-- WATERMARK --}}
    @if ($sale->remaining_balance <= 0)
        <div class="watermark">PAGADO</div>
    @endif

    <div class="container">
        <div class="header">

            <!-- ENCABEZADO SUPERIOR -->
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <!-- LOGO -->
                    <td style="width:25%; text-align:left;">
                        <img src="{{ public_path('assets/img/logo.webp') }}" style="max-width:120px;">
                    </td>

                    <!-- EMPRESA -->
                    <td style="width:50%; text-align:center;">
                        <h3 style="margin:0;">{{ $sale->branch->name }}</h3>
                        <p style="margin:2px 0;">{{ $sale->branch->address }}</p>
                        <p style="margin:2px 0;">Tel: {{ $sale->branch->phone }}</p>
                    </td>

                    <!-- TIPO -->
                    <td style="width:25%; text-align:right;">
                        <h3 style="margin:0;">RECIBO DE PAGO</h3>
                    </td>
                </tr>
            </table>

            <hr style="border-top:1px dashed #000; margin:8px 0;">

            <!-- DATOS DEL RECIBO -->
            <table style="width:100%; font-size:11px; margin-top:5px;">
                <tr>
                    <td style="width:25%;"><strong>Recibo Nº:</strong></td>
                    <td style="width:25%;">{{ $sale->internal_number ?? $sale->id }}</td>

                    <td style="width:25%;"><strong>Fecha:</strong></td>
                    <td style="width:25%;">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <td><strong>Cliente:</strong></td>
                    <td>{{ $sale->customer_name }}</td>

                    <td><strong>Vendedor:</strong></td>
                    <td>{{ $sale->user->name }}</td>
                </tr>
            </table>

        </div>

        {{-- ITEMS --}}
        <div class="items">
            <table>
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th class="text-right">Importe</th>
                    </tr>
                </thead>
                <tbody>

                    @php $subtotal = 0; @endphp

                    @foreach ($sale->items as $item)
                        @php $subtotal += $item->subtotal; @endphp
                        <tr>
                            <td>
                                {{ $item->quantity }}x {{ $item->descriptionForReceipt($sale) }}
                            </td>
                            <td class="text-right">
                                @if ($sale->sale_type === \App\Enums\SaleType::Repair)
                                    @if ($loop->first)
                                        ${{ number_format($sale->total_general_ars, 2, ',', '.') }}
                                    @endif
                                @else
                                    ${{ number_format($item->subtotal, 2, ',', '.') }}
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    <tr class="total-row">
                        <td>SUBTOTAL</td>
                        <td class="text-right">
                            ${{ number_format(
                                $sale->sale_type === \App\Enums\SaleType::Repair ? $sale->total_general_ars : $subtotal,
                                2,
                                ',',
                                '.',
                            ) }}
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        {{-- PAGOS --}}
        <div class="items">
            <table>
                <thead>
                    <tr>
                        <th>Medio de Pago</th>
                        <th class="text-right">Monto</th>
                    </tr>
                </thead>
                <tbody>

                    @php $totalPagado = 0; @endphp

                    @foreach ($sale->payments as $payment)
                        @php $totalPagado += $payment->amount; @endphp
                        <tr>
                            <td>{{ $payment->payment_type->label() }}</td>
                            <td class="text-right">
                                ${{ number_format($payment->amount, 2, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach

                    <tr class="total-row">
                        <td>TOTAL PAGADO</td>
                        <td class="text-right">
                            ${{ number_format($totalPagado, 2, ',', '.') }}
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        {{-- TOTALES --}}
        <div class="details">
            @if ($sale->discount_amount > 0)
                <p><strong>Descuento:</strong>
                    -${{ number_format($sale->discount_amount, 2, ',', '.') }}
                </p>
            @endif

            <p><strong>Total:</strong>
                ${{ number_format($sale->total_general_ars, 2, ',', '.') }}
            </p>

            <p><strong>Saldo pendiente:</strong>
                ${{ number_format($sale->remaining_balance, 2, ',', '.') }}
            </p>
        </div>

        <div style="page-break-before: always;"></div>

        @include('pdf.partials.terms')

    </div>
</body>

</html>
