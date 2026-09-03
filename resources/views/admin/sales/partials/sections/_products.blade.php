{{-- <div class="row g-3 mb-3">
    <div class="col-md-4">
        <label for="product_search_code" class="form-label">Agregar Producto</label>

        <div class="input-group">
            <input type="text" id="product_search_code" class="form-control" placeholder="Código de producto (SKU)"
                autocomplete="off">

            <button type="button" class="btn btn-custom btn-custom-aqua" id="btn-open-product-modal">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>
</div> --}}

@php
    $canViewCost = auth()->user()?->hasAnyRole([\App\Enums\RoleLabel::ADMIN->value, \App\Enums\RoleLabel::PROVINCIAL_ADMIN->value]) ?? false;
@endphp

{{-- Tabla --}}
<div class="table-responsive">
    <table class="table table-striped table-bsaleed align-middle" id="order-items-table">
        <thead>
            <tr>
                <th width="18%">Producto</th>
                <th width="7%">Stock</th>
                @if ($canViewCost)
                    <th width="14%">Costo</th>
                @endif
                <th width="{{ $canViewCost ? '16%' : '20%' }}">Precio</th>
                <th width="6%">Cantidad</th>
                <th width="15%">Subtotal</th>
                <th width="8%"></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
