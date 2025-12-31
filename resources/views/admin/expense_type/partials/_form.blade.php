@props(['formData'])

<div class="row g-3 mb-4">
    <div class="col-md-12">
        {{-- Nombre del Tipo de Gasto --}}
        <x-adminlte.input-group id="name" name="name" label="Nombre del Tipo de Gasto"
            placeholder="Ej: Servicios, Insumos, Transporte" :value="old('name', $formData['expenseType']?->name)" required />
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-12">
        {{-- Descripción opcional --}}
        <x-adminlte.textarea id="description" name="description" label="Descripción"
            placeholder="Detalle opcional del tipo de gasto" :value="old('description', $formData['expenseType']?->description)" />
    </div>
</div>
