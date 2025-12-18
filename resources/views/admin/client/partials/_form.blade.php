@props([
    'client' => null,
])

{{-- Sección 1: Datos Personales --}}
<h5 class="mb-3">Datos Personales</h5>
<div class="row g-3">
    <div class="col-md-6">
        <x-admin-lte.input-group id="full_name" name="full_name" label="Nombre Completo *" placeholder="Ej: Juan Pérez"
            :value="old('full_name', $client->full_name ?? '')" required />
    </div>

    <div class="col-md-6">
        <x-admin-lte.input-group id="document" name="document" label="Documento de Identidad *"
            placeholder="Ej: 12345678" :value="old('document', $client->document ?? '')" required />
    </div>
</div>

<hr class="my-4">

{{-- Sección 2: Contacto --}}
<h5 class="mb-3">Datos de Contacto</h5>
<div class="row g-3">
    <div class="col-md-6">
        <x-admin-lte.input-group id="email" type="email" name="email" label="Email"
            placeholder="Ej: cliente@email.com" :value="old('email', $client->email ?? '')" />
    </div>

    <div class="col-md-6">
        <x-admin-lte.input-group id="phone" name="phone" label="Teléfono *" placeholder="Ej: +54 9 11 5555-1234"
            :value="old('phone', $client->phone ?? '')" required />
    </div>
</div>

<hr class="my-4">

{{-- Sección 3: Ubicación (opcional) --}}
<h5 class="mb-3">Ubicación (opcional)</h5>
<div class="row g-3">
    <div class="col-md-12">
        <x-admin-lte.textarea id="address" name="address" label="Dirección" :value="old('address', $client->address ?? '')"
            placeholder="Ej: Av. Cabildo 1234, Buenos Aires" rows="3" />
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const documentField = document.getElementById('document');
            if (documentField) {
                documentField.addEventListener('blur', function() {
                });
            }
        });
    </script>
@endpush
