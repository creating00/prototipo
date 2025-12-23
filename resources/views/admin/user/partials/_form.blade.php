@props(['formData'])

<div class="form-section">
    <h3 class="form-section-title">Información de Cuenta</h3>

    <div class="row g-3">
        {{-- Nombre del Usuario --}}
        <div class="col-md-6">
            <x-bootstrap.compact-input id="name" name="name" label="Nombre Completo" placeholder="Ej: Juan Pérez"
                value="{{ old('name', $formData->user?->name ?? '') }}" required />
        </div>

        {{-- Correo Electrónico --}}
        <div class="col-md-6">
            <x-bootstrap.compact-input id="email" name="email" type="email" label="Correo Electrónico"
                placeholder="ejemplo@correo.com" value="{{ old('email', $formData->user?->email ?? '') }}" required />
        </div>

        {{-- Contraseña (Solo obligatoria en creación) --}}
        <div class="col-md-6">
            <x-bootstrap.compact-input id="password" name="password" type="password" label="Contraseña"
                placeholder="Mínimo 8 caracteres" :required="!isset($formData->user)" />
            @if (isset($formData->user))
                <small class="text-muted">Dejar en blanco para mantener la actual.</small>
            @endif
        </div>

        {{-- Confirmar Contraseña --}}
        <div class="col-md-6">
            <x-bootstrap.compact-input id="password_confirmation" name="password_confirmation" type="password"
                label="Confirmar Contraseña" placeholder="Repita la contraseña" :required="!isset($formData->user)" />
        </div>
    </div>
</div>

<hr class="my-3">

<div class="form-section">
    <h3 class="form-section-title">Asignación y Permisos</h3>

    <div class="row g-3">
        {{-- Sucursal --}}
        <div class="col-md-6">
            <x-admin-lte.select-with-action name="branch_id" label="Sucursal Asignada" :options="$formData->branches->pluck('name', 'id')->toArray()"
                :value="old('branch_id', $formData->user?->branch_id ?? '')" required buttonId="btn-new-branch" />
        </div>

        {{-- Rol del Usuario --}}
        <div class="col-md-6">
            <x-admin-lte.select name="role" label="Rol del Usuario" :options="$formData->roles
                ->pluck('name')
                ->mapWithKeys(
                    fn($name) => [
                        $name => \App\Enums\RoleLabel::labelFrom($name),
                    ],
                )
                ->toArray()" :value="old('role', $formData->user?->roles->first()?->name ?? '')" required />
        </div>

        {{-- Estado del Usuario (Activo/Inactivo) --}}
        {{-- <div class="col-md-6">
            <x-admin-lte.select name="status" label="Estado de Cuenta" :options="$formData->statusOptions"
                placeholder="Seleccione estado" :value="old('status', $formData->user?->status ?? 'active')" required />
        </div> --}}
    </div>
</div>
