<div class="row g-3">

    {{-- Usuario --}}
    <div class="col-md-6">
        <div class="compact-select-wrapper">
            <label class="compact-select-label">
                Usuario <span class="text-danger">*</span>
            </label>

            <x-adminlte.select name="user_id" :options="$formData->users" :value="old('user_id', $formData->bankAccount?->user_id)" :showPlaceholder="true"
                placeholder="Seleccione un usuario" required />
        </div>
    </div>

    {{-- Banco --}}
    <div class="col-md-6">
        <div class="compact-select-wrapper">
            <label class="compact-select-label">
                Banco <span class="text-danger">*</span>
            </label>

            <x-adminlte.select name="bank_id" :options="$formData->banks" :value="old('bank_id', $formData->bankAccount?->bank_id)" :showPlaceholder="true"
                placeholder="Seleccione un banco" required />
        </div>
    </div>

    {{-- Alias --}}
    <div class="col-md-6">
        <x-bootstrap.compact-input id="alias" name="alias" label="Alias"
            placeholder="Ej: Cuenta sueldo, Banco empresa" :value="old('alias', $formData->bankAccount?->alias)" />
    </div>

    {{-- Número de cuenta --}}
    <div class="col-md-6">
        <x-bootstrap.compact-input id="account_number" name="account_number" label="Número de cuenta"
            placeholder="Ej: 00123456789" :value="old('account_number', $formData->bankAccount?->account_number)" />
    </div>

    {{-- CBU --}}
    <div class="col-md-6">
        <x-bootstrap.compact-input id="cbu" name="cbu" label="CBU" placeholder="Ej: 2850590940090418135201"
            :value="old('cbu', $formData->bankAccount?->cbu)" />
    </div>

</div>
