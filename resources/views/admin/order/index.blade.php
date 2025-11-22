@extends('adminlte::page')

@section('title', 'Pedidos')

@section('content_header')
    <h1>Pedidos</h1>
@stop

@section('content')
    <a href="{{ route('order.create') }}" class="btn btn-primary mb-3">Nuevo Pedido</a>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped" id="tableOrders">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Productos</th>
                        <th>Cantidad Total</th>
                        <th>Monto Total</th>
                        <th>Usuario</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    @include('admin.payment.partials._payment')
@stop

@section('js')
    <script>
        window.authUserId = {{ auth()->id() }};
    </script>
    <script src="{{ asset('js/order/order-payment-processor.js') }}"></script>
    <script src="{{ asset('js/order/payment-modal.js') }}"></script>
    <script src="{{ asset('js/order/order-row-builder.js') }}"></script>
    <script src="{{ asset('js/order/order-index-handler.js') }}"></script>
    <script src="{{ asset('js/order-index-init.js') }}"></script>

    <script>
        window.authUserId = {{ auth()->id() }};
    </script>

    <style>
        #tableOrders td {
            vertical-align: middle;
        }
    </style>
@stop
