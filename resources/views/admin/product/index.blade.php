@extends('adminlte::page')

@section('title', 'Productos')

@section('content_header')
    <h1>Productos</h1>
@endsection

@section('content')

    <a href="{{ route('product.create') }}" class="btn btn-primary mb-3">Nuevo Producto</a>

    <template id="product-row-template">
        <tr>
            <td class="col-img"></td>
            <td class="col-code"></td>
            <td class="col-name"></td>
            <td class="col-category"></td>
            <td class="col-stock"></td>
            <td class="col-branch"></td>
            <td class="col-price"></td>
            <td class="col-rating"></td>
            <td class="col-actions"></td>
        </tr>
    </template>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered" id="tableProducts">
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Stock</th>
                        <th>Sucursal</th>
                        <th>Precio Venta</th>
                        <th>Rating</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

@endsection

@section('js')
    <script src="/js/product/product-index-service.js"></script>
    <script src="/js/product/product-table.js"></script>
    <script src="/js/product/product-index.js"></script>

@endsection
