{{-- Totales --}}
{{-- <div id="sale-total-wrapper">
    <x-bootstrap.compact-input id="subtotal_amount" name="subtotal_amount" type="number" label="Subtotal" step="0.01"
        readonly prefix="$" value="{{ old('subtotal_amount', $sale->subtotal_amount ?? 0) }}" />

    <div class="mb-3">
        <label for="discount_id" class="form-label">Descuento</label>
        <select name="discount_id" id="discount_id" class="form-control">
            <option value="" placeholder selected>Sin descuento</option>
            @foreach ($discountOptions as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>

    <x-bootstrap.compact-input id="discount_amount" name="discount_amount" type="number" label="Descuento aplicado"
        step="0.01" readonly prefix="$" value="{{ old('discount_amount', $sale->discount_amount ?? 0) }}" />

    <x-bootstrap.compact-input id="total_amount" name="total_amount" type="number" label="Total del Pedido"
        step="0.01" readonly prefix="$" value="{{ old('total_amount', $sale->total_amount ?? 0) }}" />
</div> --}}

<div id="sale-total-wrapper" class="mb-3">

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
            <label for="discount_id" class="form-label small mb-1">
                Descuento
            </label>
            <select name="discount_id_modal" id="discount_id" class="form-control form-control-sm">
                <option value="" selected>Sin descuento</option>
                @foreach ($discountOptions as $id => $displayName)
                    <option value="{{ $id }}">{{ $displayName }}</option>
                @endforeach
            </select>
        </div>

        {{-- Descuento aplicado --}}
        <div class="col-6">
            <small class="text-muted">Descuento aplicado</small>
            <div class="fw-semibold text-danger">
                - $ <span id="discount_amount_display">
                    {{ number_format(old('discount_amount', $sale->discount_amount ?? 0), 2) }}
                </span>
            </div>
            <input type="hidden" name="discount_amount" id="discount_amount"
                value="{{ old('discount_amount', $sale->discount_amount ?? 0) }}">
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
</div>

<div id="repair-amount-wrapper" class="d-none">
    <x-bootstrap.compact-input id="repair_amount" name="repair_amount" type="number" label="Costo de la Reparación"
        step="0.01" prefix="$" value="{{ old('repair_amount', $sale->repair_amount ?? '') }}"
        oninput="window.salePayment?.setSaleTotalFromRepair()" required />
</div>

<hr class="my-3">

{{-- Datos de pago --}}
<div class="row g-3 equal-height-selects align-items-center">
    <div class="col-md-3">
        <x-bootstrap.compact-input id="sale_date" name="sale_date_modal" type="date" label="Fecha de Venta"
            value="{{ $saleDate }}" />
    </div>

    <div class="col-md-3 compact-select-wrapper">
        <x-adminlte.select name="payment_type_modal" label="" :options="$paymentOptions" :value="old('payment_type', $sale->payment_type ?? 1)" />
    </div>

    <div class="col-md-3">
        <x-bootstrap.compact-input id="amount_received" name="amount_received_modal" type="number"
            label="Monto Recibido" step="0.01" prefix="$"
            value="{{ old('amount_received', $sale->amount_received ?? '') }}" />
    </div>

    <div class="col-md-3">
        <x-bootstrap.compact-input id="change_returned" name="change_returned" type="number" label="Cambio Devuelto"
            step="0.01" prefix="$" value="{{ old('change_returned', $sale->change_returned ?? 0) }}" readonly />
    </div>

    <div class="col-md-3">
        <x-bootstrap.compact-input id="remaining_balance" name="remaining_balance" type="number"
            label="Saldo Pendiente" step="0.01" prefix="$"
            value="{{ old('remaining_balance', $sale->remaining_balance ?? 0) }}" readonly />
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label>Estado del Pago</label>
            <div id="payment_status_indicator" class="mt-2">
                <span class="badge bg-secondary">Esperando datos...</span>
            </div>
        </div>
    </div>
</div>
