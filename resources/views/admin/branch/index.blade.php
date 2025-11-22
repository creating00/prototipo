@extends('adminlte::page')

@section('title', 'Sucursales')

@section('content_header')
    <h1>Sucursales</h1>
@endsection

@section('content')

    <div class="mb-3">
        <a href="{{ route('branch.create') }}" class="btn btn-primary">
            Nueva sucursal
        </a>
    </div>

    <!-- Template para la fila -->
    <template id="branch-row-template">
        <tr>
            <td class="col-id"></td>
            <td class="col-name"></td>
            <td class="col-address"></td>
            <td class="col-actions"></td>
        </tr>
    </template>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped" id="tableBranches">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Dirección</th>
                        <th>Opciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

@endsection

@section('js')
    <script src="/js/branch/branch-service.js"></script>
    <script src="/js/branch/branch-table.js"></script>
    <script src="/js/branch/branch-index.js"></script>
@endsection
