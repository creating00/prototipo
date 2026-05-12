@props(['formData'])

@php
    use App\Enums\PaymentType;
@endphp

@push('styles')
    @vite('resources/css/modules/products/products-styles.css')
@endpush

<h3>Información del Gasto</h3>

<div class="row g-3 align-items-start">
    {{-- Fila 1: Fecha + Forma de Pago --}}
    <div class="col-md-6">
        <x-bootstrap.compact-input id="date" name="date" type="date" label="Fecha del Gasto" :value="old('date', $formData->expense?->date?->format('Y-m-d') ?? now()->format('Y-m-d'))"
            required />
    </div>

    <div class="col-md-6">
        <div class="compact-select-wrapper">
            <label class="compact-select-label">Forma de Pago</label>
            <x-adminlte.select name="payment_type" label="" :options="PaymentType::forSelect()" :value="old('payment_type', $formData->expense?->payment_type->value ?? PaymentType::Cash->value)" required />
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Fila 2: Monto + Observación --}}
    <div class="col-md-6">
        <x-currency-price-input name="amount" label="Monto del Gasto" :amount-value="old('amount_amount', $formData->expense?->amount)" :currency-value="old('amount_currency', $formData->currency())"
            :currency-options="$formData->currencyOptions" :required="true" />
    </div>

    <div class="col-md-6">
        <x-adminlte.textarea id="observation" name="observation" label="Motivo del Gasto" rows="2"
            placeholder="Escriba el motivo del gasto..." :value="old('observation', $formData->expense?->observation)" required />
    </div>
</div>

{{-- Campo oculto --}}
<input type="hidden" name="expense_type_id" value="{{ old('expense_type_id', $formData->expense?->expense_type_id) }}">
