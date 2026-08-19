@props(['formData'])

@php
    use App\Enums\PaymentType;
@endphp

@push('styles')
    @vite('resources/css/modules/products/products-styles.css')
@endpush

<h3>Información del Gasto</h3>

<div class="row g-3 align-items-start" x-data="{ paymentType: '{{ old('payment_type', $formData->expense?->payment_type->value ?? PaymentType::Cash->value) }}' }" @change="if ($event.target.name === 'payment_type') paymentType = $event.target.value">
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

    {{-- Cuenta Destino (Solo transferencia) --}}
    <div class="col-md-6" x-show="paymentType == '3'" x-cloak x-transition>
        <div class="compact-select-wrapper">
            <label class="compact-select-label">Cuenta Destino <span class="text-danger">*</span></label>
            <x-adminlte.select name="bank_account_id" label="" :options="$formData->bankAccounts" :value="old('bank_account_id', $formData->expense?->bank_account_id)" />
        </div>
    </div>

    {{-- Sucursal (si el usuario no tiene sucursal fija o posee múltiples) --}}
    @if(!$formData->branchUserId || $formData->branches->count() > 1)
        <div class="col-md-6">
            <div class="compact-select-wrapper">
                <label class="compact-select-label">Sucursal Imputable <span class="text-danger">*</span></label>
                <select name="branch_id" id="branch_id" class="form-select" required>
                    <option value="">Seleccione la sucursal...</option>
                    @foreach($formData->branches as $b)
                        <option value="{{ $b->id }}" {{ old('branch_id', $formData->expense?->branch_id ?? $formData->branchUserId) == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif
</div>

<div class="row g-3">
    {{-- Fila 2: Monto + Motivo (Select) --}}
    <div class="col-md-6">
        <x-currency-price-input name="amount" label="Monto del Gasto" :amount-value="old('amount_amount', $formData->expense?->amount)" :currency-value="old('amount_currency', $formData->currency())"
            :currency-options="$formData->currencyOptions" :required="true" />
    </div>

    <div class="col-md-6">
        <label class="form-label">Motivo del Gasto <span class="text-danger">*</span></label>
        <div class="input-group">
            <select name="expense_type_id" id="expense_type_id" class="form-select" required>
                <option value="">Seleccione el motivo...</option>
                @foreach($formData->expenseTypes as $type)
                    <option value="{{ $type->id }}" {{ old('expense_type_id', $formData->expense?->expense_type_id) == $type->id ? 'selected' : '' }}>
                        {{ $type->display_name }}
                    </option>
                @endforeach
            </select>
            <button type="button" class="btn btn-outline-primary" id="btn-new-expense-type" title="Crear nuevo motivo">
                <i class="fas fa-plus"></i>
            </button>
        </div>
        @error('expense_type_id')
            <div class="text-danger small">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-md-12">
        <x-adminlte.textarea id="observation" name="observation" label="Observación adicional" rows="2"
            placeholder="Opcional: detalles adicionales del gasto..." :value="old('observation', $formData->expense?->observation)" />
    </div>
</div>
