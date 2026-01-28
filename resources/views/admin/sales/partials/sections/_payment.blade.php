<div class="row g-2">

    {{-- Subtotal --}}
    <div class="col-6">
        <small class="text-muted">Subtotal</small>
        <div class="fw-semibold fs-6">
            $ <span id="subtotal_amount_display">
                {{ number_format(old('subtotal_amount', $sale->subtotal_amount ?? 0), 2) }}
            </span>
        </div>

        <input type="hidden" name="subtotal_amount" id="subtotal_amount"
            value="{{ old('subtotal_amount', $sale->subtotal_amount ?? 0) }}">
    </div>

    {{-- Descuento --}}
    <div class="col-6">
        <small class="text-muted">Descuento</small>

        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" class="form-control" id="discount_amount_input" step="0.01" min="0"
                value="{{ old('discount_amount', $sale->discount_amount ?? 0) }}">
        </div>
    </div>

    {{-- Total --}}
    <div class="col-6">
        <small class="text-muted">Total del Pedido</small>
        <div class="fw-bold fs-5 text-success">
            $ <span id="total_amount_display">
                {{ number_format(old('total_amount', $sale->total_amount ?? 0), 2) }}
            </span>
        </div>

        <input type="hidden" name="total_amount" id="total_amount"
            value="{{ old('total_amount', $sale->total_amount ?? 0) }}">
    </div>

</div>

<hr class="my-3">

{{-- Checkbox de control --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="enable_dual_payment" {{ $isDual ? 'checked' : '' }}>
            <label class="form-check-label fw-bold" for="enable_dual_payment">
                Habilitar doble tipo de pago / Editar montos
            </label>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Bloque Pago 1 --}}
    <div class="col-md-6">
        <div class="card card-outline card-secondary shadow-none border bg-light" id="card_payment_1">
            <div class="card-body p-3">
                <label class="small text-muted mb-2 d-block text-uppercase">Método 1</label>
                <div class="mb-2">
                    <x-adminlte.select name="payment_type_1_modal" id="payment_type_1_modal" label=""
                        :options="$paymentOptions" :value="old(
                            'payment_type',
                            $pago1->payment_type->value ?? \App\Enums\PaymentType::Cash->value,
                        )" :showPlaceholder="false" disabled />
                </div>
                <x-bootstrap.compact-input id="amount_received_1_modal" name="amount_received_1_modal" type="number"
                    label="Monto" step="0.01" prefix="$" :value="old('amount_received', $pago1->amount ?? ($sale->amount_received ?? '0.00'))" readonly />
            </div>
        </div>
    </div>

    {{-- Bloque Pago 2 --}}
    <div class="col-md-6">
        <div class="card card-outline card-info shadow-none border bg-light" id="card_payment_2">
            <div class="card-body p-3">
                <label class="small text-muted mb-2 d-block text-uppercase">Método 2</label>
                <div class="mb-2">
                    <x-adminlte.select name="payment_type_2_modal" id="payment_type_2_modal" label=""
                        :options="$paymentOptions" :value="old(
                            'payment_type_2',
                            $pago2->payment_type->value ?? \App\Enums\PaymentType::Card->value,
                        )" :showPlaceholder="false" disabled />
                </div>
                <x-bootstrap.compact-input id="amount_received_2_modal" name="amount_received_2_modal" type="number"
                    label="Monto" step="0.01" prefix="$" :value="old('amount_received_2', $pago2->amount ?? '0.00')" readonly />
            </div>
        </div>
    </div>
</div>

{{-- Resumen visual (Labels) --}}
<div class="row g-3 mt-1 text-center">
    <div class="col-md-4">
        <div class="p-2 border rounded bg-white">
            <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Total Recibido</small>
            <span class="h6 mb-0 fw-bold text-dark">$ <span id="label_total_received">0.00</span></span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-2 border rounded bg-white">
            <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Cambio</small>
            <span class="h6 mb-0 fw-bold text-info">$ <span id="label_change_returned">0.00</span></span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-2 border rounded bg-white">
            <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Saldo Pendiente</small>
            <span class="h6 mb-0 fw-bold text-danger">$ <span id="label_remaining_balance">0.00</span></span>
        </div>
    </div>
</div>
