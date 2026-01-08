@props(['formData'])

@php
    use App\Enums\PaymentType;
    use App\Enums\PriceType;
@endphp

<h3>Información del Gasto</h3>
<div class="row g-3">
    <div class="col-md-6">
        {{-- Sucursal --}}
        <x-adminlte.select-with-action name="branch_id" label="Sucursal" :options="$formData->branches->pluck('name', 'id')->toArray()" :value="old('branch_id', $formData->expense?->branch_id ?? $formData->branchUserId)" required
            buttonId="btn-new-branch" />
    </div>

    <div class="col-md-6">
        {{-- Tipo de Gasto --}}
        <x-adminlte.select-with-action name="expense_type_id" label="Tipo de Gasto" :options="$formData->expenseTypes->pluck('display_name', 'id')->toArray()" :value="old('expense_type_id', $formData->expense?->expense_type_id)"
            required buttonId="btn-new-expense-type" />
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        {{-- Este componente ya trae su lógica interna --}}
        <x-currency-price-input name="amount" label="Monto del Gasto" :amount-value="old('amount.amount', $formData->expense?->amount)" :currency-value="old('amount.currency', $formData->currency())"
            :currency-options="$formData->currencyOptions" :required="true" />
    </div>

    <div class="col-md-6">
        <div class="compact-select-wrapper">
            <label class="compact-select-label">Forma de Pago</label>
            <x-adminlte.select name="payment_type" label="" :options="PaymentType::forSelect()" :value="old('payment_type', $formData->expense?->payment_type->value ?? PaymentType::Cash->value)" required />
        </div>
    </div>
</div>

<hr class="my-3">

<h3>Referencia</h3>
<div class="row g-3">
    <div class="col-md-12">
        {{-- Referencia (ej: número de factura) --}}
        <x-adminlte.input-group id="reference" name="reference" label="Referencia / Factura"
            placeholder="Ej: Factura #123" :value="old('reference', $formData->expense?->reference)" />
    </div>
</div>
