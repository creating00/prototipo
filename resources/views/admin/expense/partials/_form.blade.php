@props(['formData'])

@php
    use App\Enums\PaymentType;
    use App\Enums\PriceType;
@endphp

<h3>Información del Gasto</h3>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        {{-- Sucursal --}}
        <x-admin-lte.select-with-action name="branch_id" label="Sucursal" :options="$formData->branches->pluck('name', 'id')->toArray()" :value="old('branch_id', $formData->expense?->branch_id ?? $formData->branchUserId)" required
            buttonId="btn-new-branch" />
    </div>

    <div class="col-md-6">
        {{-- Tipo de Gasto --}}
        <x-admin-lte.select-with-action name="expense_type_id" label="Tipo de Gasto" :options="$formData->expenseTypes->pluck('display_name', 'id')->toArray()" :value="old('expense_type_id', $formData->expense?->expense_type_id)"
            required buttonId="btn-new-expense-type" />
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        {{-- Monto + Moneda --}}
        <x-currency-price-input name="amount" label="Monto del Gasto" :amount-value="old('amount.amount', $formData->expense?->amount)" :currency-value="old('amount.currency', $formData->currency())"
            :currency-options="$formData->currencyOptions" :required="true" />
    </div>

    <div class="col-md-6">
        {{-- Forma de Pago --}}
        <x-admin-lte.select name="payment_type" label="Forma de Pago" :options="PaymentType::forSelect()" :value="old('payment_type', $formData->expense?->payment_type->value ?? PaymentType::Cash->value)" required />
    </div>
</div>

<hr class="my-3">

<h3>Referencia</h3>
<div class="row g-3 mb-4">
    <div class="col-md-12">
        {{-- Referencia (ej: número de factura) --}}
        <x-admin-lte.input-group id="reference" name="reference" label="Referencia / Factura"
            placeholder="Ej: Factura #123" :value="old('reference', $formData->expense?->reference)" />
    </div>
</div>
