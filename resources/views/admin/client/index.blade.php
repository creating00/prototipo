@extends('adminlte::page')

@section('title', 'Clientes')

@section('content_header')
    <h1>Clientes</h1>
@endsection

@section('content')

    <a href="{{ route('client.create') }}" class="btn btn-primary mb-3">Nuevo Cliente</a>

    <template id="client-row-template">
        <tr>
            <td class="col-doc"></td>
            <td class="col-name"></td>
            <td class="col-phone"></td>
            <td class="col-address"></td>
            <td class="col-actions"></td>
        </tr>
    </template>
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped" id="tableClients">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Nombre Completo</th>
                        <th>Teléfono</th>
                        <th>Dirección</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('js')
    <script src="/js/client/client-service.js"></script>
    <script src="/js/client/client-table.js"></script>
    <script src="/js/client/client-index.js"></script>
@endsection
