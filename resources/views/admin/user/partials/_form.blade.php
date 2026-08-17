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
        {{-- Rol del Usuario --}}
        <div class="col-md-4">
            <div class="compact-select-wrapper">
                <label class="compact-select-label">Rol del Usuario <span class="text-danger">*</span></label>
                <x-adminlte.select name="role" :options="$formData->getRoleOptions()" :value="$formData->getSelectedRole()" required />
            </div>
        </div>

        {{-- Provincia (para Administrador Provincial) --}}
        <div class="col-md-4">
            <div class="compact-select-wrapper">
                <label class="compact-select-label">Provincia Asignada</label>
                <x-adminlte.select name="province_id" :options="$formData->getProvinceOptions()" :value="$formData->getSelectedProvinceId()" />
                <small class="text-muted d-block mt-1">Requerido para el rol de <strong>Administrador Provincial</strong>.</small>
            </div>
        </div>

        {{-- Sucursal Específica --}}
        <div class="col-md-4">
            <div class="compact-select-wrapper">
                <label class="compact-select-label">Sucursal Específica</label>
                <x-adminlte.select name="branch_id" :options="$formData->getBranchOptions()" :value="$formData->getSelectedBranchId()" />
                <small class="text-muted d-block mt-1">Obligatorio para Vendedores / Opcional para Admins.</small>
            </div>
        </div>
    </div>
</div>
