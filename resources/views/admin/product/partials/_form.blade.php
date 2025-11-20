<div class="row">

    <div class="col-md-4 mb-3">
        <label>Código</label>
        <input type="text" id="code" class="form-control" value="{{ $product->code ?? '' }}">
    </div>

    <div class="col-md-4 mb-3">
        <label>Nombre</label>
        <input type="text" id="name" class="form-control" value="{{ $product->name ?? '' }}">
    </div>

    <div class="col-md-4 mb-3">
        <label>Imagen (URL)</label>
        <input type="text" id="image" class="form-control" value="{{ $product->image ?? '' }}">
    </div>

    <div class="col-md-4 mb-3">
        <label>Categoría</label>
        <select id="category_id" class="form-control"></select>
    </div>

    <div class="col-md-4 mb-3">
        <label>Stock</label>
        <input type="number" id="stock" class="form-control" value="{{ $product->stock ?? 0 }}">
    </div>

    <div class="col-md-4 mb-3">
        <label>Sucursal</label>
        <select id="branch_id" class="form-control"></select>
    </div>

    <div class="col-md-4 mb-3">
        <label>Precio Compra</label>
        <input type="number" id="purchase_price" class="form-control" value="{{ $product->purchase_price ?? 0 }}">
    </div>

    <div class="col-md-4 mb-3">
        <label>Precio Venta</label>
        <input type="number" id="sale_price" class="form-control" value="{{ $product->sale_price ?? 0 }}">
    </div>

    <div class="col-12 mb-3">
        <label>Descripción</label>
        <textarea id="description" class="form-control">{{ $product->description ?? '' }}</textarea>
    </div>

</div>

<button class="btn btn-primary">{{ $mode === 'edit' ? 'Actualizar' : 'Guardar' }}</button>
<a href="{{ route('product.index') }}" class="btn btn-secondary">Volver</a>
