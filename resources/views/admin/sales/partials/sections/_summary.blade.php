{{-- RESUMEN DE VENTA --}}
<div class="row g-3 align-items-start">

    {{-- COLUMNA IZQUIERDA --}}
    <div class="col-md-8">
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

        {{-- Contenedor de Pagos Desglosados --}}
        <div id="wrapper_payments_breakdown" class="row g-2 mb-3">

            {{-- Efectivo --}}
            <div class="col-md-4" id="group_payment_cash">
                <div class="border rounded p-2 h-100 bg-white" style="border-top: 3px solid #28a745 !important;">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-money-bill-wave text-success me-2"></i>
                        <label
                            class="text-label mb-0"><strong>{{ \App\Enums\PaymentType::Cash->label() }}</strong></label>
                    </div>
                    <x-bootstrap.compact-input id="amount_received_cash" name="amount_received_cash" type="number"
                        label="Monto" step="0.01" prefix="$" :value="old(
                            'amount_received_cash',
                            $sale?->payments->where('payment_type', 1)->first()?->amount,
                        )" />
                </div>
            </div>

            {{-- Transferencia --}}
            <div class="col-md-4" id="group_payment_transfer">
                <div class="border rounded p-2 h-100 bg-white" style="border-top: 3px solid #17a2b8 !important;">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-university text-info me-2"></i>
                        <label
                            class="text-label mb-0"><strong>{{ \App\Enums\PaymentType::Transfer->label() }}</strong></label>
                    </div>

                    <div class="compact-select-wrapper mb-2">
                        <label class="small text-muted" style="font-size: 0.7rem;">Cuenta Destino</label>
                        <x-adminlte.select id="bank_account_id_transfer" name="bank_account_id_transfer"
                            data-type="App\Models\BankAccount" :options="$bankAccounts" :value="old(
                                'bank_account_id_transfer',
                                $sale?->payments->where('payment_type', 3)->first()?->payment_method_id,
                            )" />
                    </div>

                    <x-bootstrap.compact-input id="amount_received_transfer" name="amount_received_transfer"
                        type="number" label="Monto" step="0.01" prefix="$" :value="old(
                            'amount_received_transfer',
                            $sale?->payments->where('payment_type', 3)->first()?->amount,
                        )" />
                </div>
            </div>

            {{-- Tarjeta --}}
            <div class="col-md-4" id="group_payment_card">
                <div class="border rounded p-2 h-100 bg-white" style="border-top: 3px solid #6f42c1 !important;">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-credit-card text-purple me-2" style="color: #6f42c1;"></i>
                        <label
                            class="text-label mb-0"><strong>{{ \App\Enums\PaymentType::Card->label() }}</strong></label>
                    </div>

                    <div class="compact-select-wrapper mb-2">
                        <label class="small text-muted" style="font-size: 0.7rem;">Banco</label>
                        <x-adminlte.select id="bank_id_card" name="bank_id_card" data-type="App\Models\Bank"
                            :options="$banks" :value="old(
                                'bank_id_card',
                                $sale?->payments->where('payment_type', 2)->first()?->payment_method_id,
                            )" />
                    </div>

                    <x-bootstrap.compact-input id="amount_received_card" name="amount_received_card" type="number"
                        label="Monto" step="0.01" prefix="$" :value="old(
                            'amount_received_card',
                            $sale?->payments->where('payment_type', 2)->first()?->amount,
                        )" />
                </div>
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
    <div class="col-md-4">

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
