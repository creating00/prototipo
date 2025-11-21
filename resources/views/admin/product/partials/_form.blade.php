<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $mode === 'edit' ? 'Editar Producto' : 'Nuevo Producto' }}</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="form-group">
                    <label for="code" class="control-label">Código *</label>
                    <input type="text" id="code" name="code" class="form-control"
                        value="{{ $product->code ?? '' }}" placeholder="Ingrese el código del producto" required>
                    <small class="form-text text-muted">Código único del producto</small>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="form-group">
                    <label for="name" class="control-label">Nombre *</label>
                    <input type="text" id="name" name="name" class="form-control"
                        value="{{ $product->name ?? '' }}" placeholder="Ingrese el nombre del producto" required>
                    <small class="form-text text-muted">Nombre descriptivo del producto</small>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="form-group">
                    <label for="image" class="control-label">Imagen</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="image" name="image">
                        <label class="custom-file-label" for="image">Seleccionar archivo</label>
                    </div>
                    <small class="form-text text-muted">Formatos: JPG, PNG, GIF (Máx. 2MB)</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="form-group">
                    <label for="category_id" class="control-label">Categoría *</label>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <option value="">Seleccione una categoría</option>
                    </select>
                    <small class="form-text text-muted">Categoría a la que pertenece el producto</small>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="form-group">
                    <label for="stock" class="control-label">Stock *</label>
                    <input type="number" id="stock" name="stock" class="form-control"
                        value="{{ $product->stock ?? 0 }}" min="0" step="1" required>
                    <small class="form-text text-muted">Cantidad disponible en inventario</small>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="form-group">
                    <label for="branch_id" class="control-label">Sucursal *</label>
                    <select id="branch_id" name="branch_id" class="form-control" required>
                        <option value="">Seleccione una sucursal</option>
                    </select>
                    <small class="form-text text-muted">Sucursal donde se encuentra el producto</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="form-group">
                    <label for="purchase_price" class="control-label">Precio Compra *</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">$</span>
                        </div>
                        <input type="number" id="purchase_price" name="purchase_price" class="form-control"
                            value="{{ $product->purchase_price ?? 0 }}" min="0" step="0.01" required>
                    </div>
                    <small class="form-text text-muted">Precio al que se compró el producto</small>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="form-group">
                    <label for="sale_price" class="control-label">Precio Venta *</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">$</span>
                        </div>
                        <input type="number" id="sale_price" name="sale_price" class="form-control"
                            value="{{ $product->sale_price ?? 0 }}" min="0" step="0.01" required>
                    </div>
                    <small class="form-text text-muted">Precio al que se venderá el producto</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 mb-3">
                <div class="form-group">
                    <label for="description" class="control-label">Descripción</label>
                    <textarea id="description" name="description" class="form-control" rows="4"
                        placeholder="Ingrese una descripción del producto">{{ $product->description ?? '' }}</textarea>
                    <small class="form-text text-muted">Descripción detallada del producto (opcional)</small>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer text-right">
        <a href="{{ route('product.index') }}" class="btn btn-secondary mr-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> {{ $mode === 'edit' ? 'Actualizar' : 'Guardar' }}
        </button>
    </div>
</div>
