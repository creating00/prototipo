{{-- RESUMEN DE VENTA --}}
<div class="row g-3 align-items-start">

    {{-- COLUMNA IZQUIERDA --}}
    <div class="col-md-6">
        <div class="d-flex align-items-center gap-2 mb-1">
            <div class="flex-grow-1">
                <x-bootstrap.compact-input id="sale_date" name="sale_date_visible" type="date" label="Fecha"
                    value="{{ $saleDate }}" />
            </div>

            <div class="mt-4">
                <x-adminlte.checkbox name="pay_in_dollars" id="pay_in_dollars" label="Cobrar en dólares" value="1"
                    :checked="$isDollarSale" />
            </div>
        </div>

        {{-- Contenedor para Pago Único --}}
        <div id="wrapper_single_payment">
            <div class="mb-1">
                <div class="compact-select-wrapper">
                    <label class="compact-select-label">Tipo de Pago</label>
                    <x-adminlte.select id="payment_type_visible" name="payment_type_visible" label="" :options="$paymentOptions" :value="old('payment_type', $pago1->payment_type->value ?? 1)"
                        :showPlaceholder="false" />
                </div>
            </div>

            {{-- Contenedor Banco (Tarjeta) --}}
            <div class="mb-1 d-none" id="container_payment_method_bank">
                <div class="compact-select-wrapper">
                    <label class="compact-select-label">Banco</label>
                    <x-adminlte.select id="bank_id_visible" name="bank_id_visible" data-type="App\Models\Bank" {{-- Atributo para JS --}}
                        :options="$banks" :value="old(
                            'payment_method_id',
                            $pago1 && $pago1->payment_method_type == 'App\Models\Bank' ? $pago1->payment_method_id : '',
                        )" />
                </div>
            </div>

            {{-- Contenedor Cuenta (Transferencia) --}}
            <div class="mb-1 d-none" id="container_payment_method_account">
                <div class="compact-select-wrapper">
                    <label class="compact-select-label">Cuenta de Destino</label>
                    <x-adminlte.select id="bank_account_id_visible" name="bank_account_id_visible" data-type="App\Models\BankAccount"
                        {{-- Atributo para JS --}} :options="$bankAccounts" :value="old(
                            'payment_method_id',
                            $pago1 && $pago1->payment_method_type == 'App\Models\BankAccount'
                                ? $pago1->payment_method_id
                                : '',
                        )" />
                </div>
            </div>

            <div>
                <x-bootstrap.compact-input id="amount_received" name="amount_received_visible" type="number"
                    label="Monto Recibido" step="0.01" prefix="$"
                    value="{{ old('amount_received', $pago1->amount ?? ($sale->amount_received ?? '')) }}" />
            </div>
        </div>

        {{-- Contenedor para Vista Doble Pago (Oculto por defecto) --}}
        <div id="wrapper_dual_payment_info" class="d-none border rounded p-2 bg-light">
            <small class="text-muted d-block mb-1 text-uppercase" style="font-size: 0.65rem;">Desglose de Pago
                Doble</small>
            <div class="d-flex justify-content-between small">
                <span id="summary_payment_type_1_label">Método 1</span>
                <span class="fw-bold"><span class="summary-symbol">$</span> <span
                        id="summary_amount_1_label">{{ number_format($pago1->amount ?? 0, 2, '.', '') }}</span></span>
            </div>
            <div class="d-flex justify-content-between small">
                <span id="summary_payment_type_2_label">Método 2</span>
                <span class="fw-bold"><span class="summary-symbol">$</span> <span
                        id="summary_amount_2_label">{{ number_format($pago2->amount ?? 0, 2, '.', '') }}</span></span>
            </div>
        </div>
    </div>

    {{-- COLUMNA DERECHA --}}
    <div class="col-md-6">

        <div class="d-flex justify-content-between align-items-center mb-1 p-2 bg-light rounded border">
            <small class="text-muted fw-bold text-uppercase">Cotización USD Blue</small>
            <div class="d-flex align-items-center">
                <span class="me-2 fw-bold text-primary">$</span>
                <input type="number" id="exchange_rate_blue" class="form-control form-control-sm text-end fw-bold"
                    style="width: 100px;" value="1000" step="1" readonly>
            </div>
        </div>

        <div class="d-flex justify-content-between mb-1 border-top pt-1" id="wrapper_usd_total">
            <small class="text-muted text-uppercase">Equivalente USD</small>
            <span class="fw-bold text-primary">
                U$D <span id="summary_total_usd">0.00</span>
            </span>
        </div>

        <div class="d-flex justify-content-between mb-1">
            <small class="text-muted">Subtotal</small>
            <span class="fw-semibold">$ <span id="summary_subtotal">0.00</span></span>
        </div>

        <div class="d-flex justify-content-between mb-1">
            <small class="text-muted">Descuento</small>
            <span class="fw-semibold text-danger">
                - $ <span id="summary_discount">0.00</span>
            </span>
        </div>

        <hr class="my-2">

        <div class="d-flex justify-content-between mb-1">
            <small class="text-muted">Total</small>
            <span class="fw-bold text-success fs-6">
                <span class="summary-symbol">$</span> <span id="summary_total">0.00</span>
            </span>
        </div>

        <div class="d-flex justify-content-between mb-1">
            <small class="text-muted">Saldo Pendiente</small>
            <span class="fw-semibold text-warning">
                <span class="summary-symbol">$</span> <span id="summary_remaining">0.00</span>
            </span>
        </div>

        <div class="d-flex justify-content-between">
            <small class="text-muted">Cambio</small>
            <span class="fw-semibold text-info">
                <span class="summary-symbol">$</span> <span id="summary_change">0.00</span>
            </span>
        </div>

    </div>
</div>

<hr class="my-3">

{{-- Notas --}}
<div class="row">
    <div class="col">
        <x-bootstrap.compact-text-area id="notes" name="notes" label="Notas" rows="2"
            placeholder="Observaciones..." value="{{ old('notes', $sale->notes ?? '') }}" />
    </div>
</div>
