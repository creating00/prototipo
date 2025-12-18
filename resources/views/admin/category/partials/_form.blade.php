{{-- Formulario reutilizable para Create y Edit --}}

<div class="row g-3">

    {{-- Nombre --}}
    <x-admin-lte.input-group id="name" name="name" label="Nombre de la Categoría" placeholder="Ingrese el nombre"
        :value="old('name', $category->name ?? '')" required />
</div>
