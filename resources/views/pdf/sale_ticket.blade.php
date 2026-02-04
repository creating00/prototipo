<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Recibo de Pago</title>

    <style>
        @page {
            margin: 0;
            padding: 0;
        }

        body {
            width: 70mm;
            margin: 0;
            padding: 5mm;
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 10px;
        }

        * {
            box-sizing: border-box;
        }

        .header {
            text-align: center;
            margin-bottom: 5px;
        }

        .container {
            min-height: 120px;
            position: relative;
        }

        .header .logo {
            margin-bottom: 5px;
        }

        .header .company-info p {
            margin: 0;
            font-size: 9px;
            line-height: 1.1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        th,
        td {
            border: none;
            padding: 1px 0;
            text-align: left;
            vertical-align: top;
        }

        th {
            border-bottom: 1px dashed #000;
            padding-bottom: 3px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row td {
            border-top: 1px dashed #000;
            padding-top: 3px;
            font-weight: bold;
        }

        .watermark {
            position: absolute;
            top: 30%;
            left: 20%;
            width: 60%;
            font-size: 60px;
            color: rgba(0, 0, 0, 0.1);
            transform: rotate(-45deg);
            text-align: center;
            pointer-events: none;
            z-index: 1000;
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

            {{-- LOGO --}}
            <div class="logo">
                <img src="{{ $sale->branch->logo_path }}" style="max-width:80px">
            </div>

            {{-- EMPRESA --}}
            <h3>{{ $sale->branch->name }}</h3>

            <div class="company-info">
                <p>{{ $sale->branch->address }}</p>
                <p>Tel: {{ $sale->branch->phone }}</p>
            </div>

            <hr style="border-top: 1px dashed #000; margin: 5px 0;">

            <h3>RECIBO DE PAGO</h3>
        </div>

        {{-- DATOS --}}
        <div class="details">
            <p><strong>Recibo Nº:</strong> {{ str_pad($sale->internal_number ?? $sale->id, 8, '0', STR_PAD_LEFT) }}</p>
            <p><strong>Fecha:</strong> {{ $sale->created_at->format('d/m/Y H:i') }}</p>
            <p><strong>Cliente:</strong> {{ $sale->customer_name }}</p>
            <p><strong>Vendedor:</strong> {{ $sale->user->name }}</p>
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
                            <td class="text-right">
                                {{ $payment->currency->code() }}
                                {{ number_format($payment->amount, 2, ',', '.') }}
                            </td>
                        </tr>

                        @if ($payment->currency !== \App\Enums\CurrencyType::ARS)
                            <tr>
                                <td colspan="2" class="text-right" style="font-size:9px;">
                                    TC:
                                    {{ number_format($payment->exchange_rate ?? $sale->exchange_rate, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endif
                    @endforeach

                    <tr class="total-row">
                        <td>TOTAL PAGADO</td>
                        <td class="text-right">
                            ${{ number_format($totalPagadoARS, 2, ',', '.') }} ARS
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        {{-- TOTALES --}}
        <div class="details" style="margin-top: 10px; border-top: 1px solid #000; padding-top: 5px;">
            @if ($sale->discount_amount > 0)
                <p><strong>Descuento:</strong>
                    -${{ number_format($sale->discount_amount, 2, ',', '.') }}
                </p>
            @endif

            <p style="font-size: 11px;"><strong>TOTAL A PAGAR:</strong>
                ${{ number_format($sale->total_general_ars, 2, ',', '.') }}
            </p>

            <p><strong>PAGADO:</strong>
                ${{ number_format($totalPagadoARS, 2, ',', '.') }}
            </p>

            @if ($sale->change_returned > 0)
                <p><strong>SU VUELTO:</strong>
                    ${{ number_format($sale->change_returned, 2, ',', '.') }}
                </p>
            @endif

            @if ($sale->remaining_balance > 0)
                <p style="color: red;"><strong>SALDO PENDIENTE:</strong>
                    ${{ number_format($sale->remaining_balance, 2, ',', '.') }}
                </p>
            @endif
        </div>

    </div>
</body>

</html>
