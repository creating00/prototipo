<div class="row g-3">
    <x-bootstrap.compact-input id="name" name="name" label="Nombre del banco"
        placeholder="Ingrese el nombre del banco" :value="old('name', $bank->name ?? '')" required />
</div>
