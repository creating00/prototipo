<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura {{ $invoice['number'] }}</title>
    @include('invoices.partials.styles')
</head>
<body>
    <div class="invoice-container">
        @include('invoices.partials.header', ['invoice' => $invoice, 'company' => $company])
        
        @include('invoices.partials.client-info', ['client' => $client])
        
        @include('invoices.partials.items-table', ['items' => $items])
        
        @include('invoices.partials.totals', ['totals' => $totals])
        
        @include('invoices.partials.footer')
    </div>
</body>
</html>