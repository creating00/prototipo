<div class="invoice-header">
    <div class="header-left">
        <div class="company-row">
            <img src="{{ public_path('images/logo.webp') }}" alt="Logo" class="company-logo">
            <div class="company-name">{{ $company['name'] }}</div>
        </div>

        <div class="company-details">
            <div>{{ $company['address'] }}</div>
            <div>{{ $company['city'] }}</div>
            <div>{{ $company['phone'] }}</div>
            <div>{{ $company['email'] }}</div>
            <div>{{ $company['tax_id'] }}</div>
        </div>
    </div>

    <div class="header-right">
        <div class="invoice-title">FACTURA</div>
        <div class="invoice-meta">
            <div><strong>Número:</strong> {{ $invoice['number'] }}</div>
            <div><strong>Fecha:</strong> {{ $invoice['date'] }}</div>
            <div><strong>Vencimiento:</strong> {{ $invoice['due_date'] }}</div>
            <div>
                <span
                    class="status-badge status-{{ strtolower($invoice['status']) == 'pagada' ? 'paid' : 'pending' }}">
                    {{ $invoice['status'] }}
                </span>
            </div>
        </div>
    </div>
</div>
