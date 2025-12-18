@extends('layouts.app')

@section('page-title', 'Detalles del Cliente')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Cliente: {{ $client->full_name }}</h3>
            <div class="card-tools">
                <a href="{{ route('web.clients.edit', $client->id) }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="{{ route('web.clients.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                {{-- Información Básica --}}
                <div class="col-md-6">
                    <div class="info-box">
                        <h5 class="text-primary">Información Básica</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Documento:</strong></td>
                                <td>{{ $client->document }}</td>
                            </tr>
                            <tr>
                                <td><strong>Nombre Completo:</strong></td>
                                <td>{{ $client->full_name }}</td>
                            </tr>
                            <tr>
                                <td><strong>Fecha de Registro:</strong></td>
                                <td>{{ $client->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                {{-- Información de Contacto --}}
                <div class="col-md-6">
                    <div class="info-box">
                        <h5 class="text-primary">Contacto</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td>
                                    <a href="mailto:{{ $client->email }}">{{ $client->email }}</a>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Teléfono:</strong></td>
                                <td>
                                    <a href="tel:{{ $client->phone }}">{{ $client->phone }}</a>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Dirección:</strong></td>
                                <td>{{ $client->address }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Órdenes del Cliente (si existen) --}}
            @if ($client->orders && $client->orders->count() > 0)
                <hr class="my-4">
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="text-primary">Órdenes Asociadas</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th># Orden</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Total</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($client->orders as $order)
                                        <tr>
                                            <td>{{ $order->order_number }}</td>
                                            <td>{{ $order->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <span
                                                    class="badge bg-{{ $order->status_color }}">{{ $order->status_label }}</span>
                                            </td>
                                            <td>${{ number_format($order->total, 2) }}</td>
                                            <td>
                                                <a href="{{ route('web.orders.show', $order->id) }}"
                                                    class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i> Este cliente no tiene órdenes registradas.
                </div>
            @endif
        </div>
        <div class="card-footer">
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">
                        <i class="fas fa-clock"></i> Última actualización: {{ $client->updated_at->diffForHumans() }}
                    </small>
                </div>
                @if ($client->deleted_at)
                    <div class="col-md-6 text-right">
                        <span class="badge bg-danger">Eliminado</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
