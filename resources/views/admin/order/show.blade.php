@extends('adminlte::page')

@section('title', 'Detalles del Pedido')

@section('content_header')
    <h1>Detalles del Pedido #{{ $order->id }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Información del Cliente</h3>
                </div>
                <div class="card-body">
                    <p><strong>Nombre:</strong> {{ $order->client->full_name }}</p>
                    <p><strong>Documento:</strong> {{ $order->client->document }}</p>
                    <p><strong>Teléfono:</strong> {{ $order->client->phone ?? 'N/A' }}</p>
                    <p><strong>Dirección:</strong> {{ $order->client->address ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Información del Pedido</h3>
                </div>
                <div class="card-body">
                    <p><strong>Fecha:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                    <p><strong>Usuario:</strong> {{ $order->user->name ?? 'Sistema' }}</p>
                    <p><strong>Total:</strong> S/. {{ $order->total_amount }}</p>
                    <p><strong>Estado:</strong>
                        <span class="badge badge-success">Activo</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title">Productos del Pedido</h3>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>{{ $item->product->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>S/. {{ $item->unit_price }}</td>
                            <td>S/. {{ $item->subtotal }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-right">Total:</th>
                        <th>S/. {{ $order->total_amount }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('order.index') }}" class="btn btn-secondary">Volver a la lista</a>
        <a href="{{ route('order.edit', $order->id) }}" class="btn btn-warning">Editar</a>
    </div>
@stop
