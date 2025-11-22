@extends('adminlte::page')

@section('title', 'Categorías')

@section('content_header')
    <h1>Categorías</h1>
@endsection

@section('content')

    <div class="mb-3">
        <a href="{{ route('category.create') }}" class="btn btn-primary">
            Nueva categoría
        </a>
    </div>

    <template id="category-row-template">
        <tr>
            <td class="col-id"></td>
            <td class="col-name"></td>
            <td class="col-actions"></td>
        </tr>
    </template>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped" id="tableCategories">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Opciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

@endsection

@section('js')
    <script src="/js/category/category-service.js"></script>
    <script src="/js/category/category-table.js"></script>
    <script src="/js/category/category-index.js"></script>
@endsection
