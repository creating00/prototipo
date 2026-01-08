@props(['formData'])

@push('styles')
    @vite('resources/css/modules/branches/branches-styles.css')
@endpush

<div class="form-section">
    <h3 class="form-section-title">Información de Cuenta</h3>

    <div class="row g-3">
        {{-- Nombre del Usuario --}}
        <div class="col-md-6">
            <x-bootstrap.compact-input id="name" name="name" label="Nombre Completo" :value="$formData->getName()" required />
        </div>

        {{-- Correo Electrónico --}}
        <div class="col-md-6">
            <x-bootstrap.compact-input id="email" name="email" type="email" label="Correo Electrónico"
                :value="$formData->getEmail()" required />
        </div>

        {{-- Contraseña --}}
        <div class="col-md-6">
            <x-bootstrap.compact-input id="password" name="password" type="password" label="Contraseña"
                placeholder="Mínimo 8 caracteres" :required="!$formData->isEdit()" />
            @if ($formData->isEdit())
                <small class="text-muted">Dejar en blanco para mantener la actual.</small>
            @endif
        </div>

        {{-- Confirmar Contraseña --}}
        <div class="col-md-6">
            <x-bootstrap.compact-input id="password_confirmation" name="password_confirmation" type="password"
                label="Confirmar Contraseña" :required="!$formData->isEdit()" />
        </div>
    </div>
</div>

<hr class="my-3">

<div class="form-section">
    <h3 class="form-section-title">Asignación y Permisos</h3>

    <div class="row g-3">
        {{-- Sucursal --}}
        <div class="col-md-6">
            <div class="compact-select-wrapper">
                <label class="compact-select-label">Sucursal Asignada <span class="text-danger">*</span></label>
                <x-adminlte.select name="branch_id" :options="$formData->getBranchOptions()" :value="$formData->getSelectedBranchId()" required />
            </div>
        </div>

        {{-- Rol del Usuario --}}
        <div class="col-md-6">
            <div class="compact-select-wrapper">
                <label class="compact-select-label">Rol del Usuario <span class="text-danger">*</span></label>
                <x-adminlte.select name="role" :options="$formData->getRoleOptions()" :value="$formData->getSelectedRole()" required />
            </div>
        </div>

        {{-- Estado --}}
        {{-- <div class="col-md-6 compact-select-wrapper">
            <label class="compact-select-label">Estado de Cuenta <span class="text-danger">*</span></label>
            <x-adminlte.select name="status" :options="$formData->statusOptions" :value="$formData->getSelectedStatus()" required />
        </div> --}}
    </div>
</div>
