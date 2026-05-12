@extends('layouts.app')

@section('page-title', 'Nueva Orden de Compra')

@section('content')
    <x-adminlte.alert-manager />
    <form action="{{ route('web.provider-orders.store') }}" method="POST" id="provider-order-form">
        @csrf
        <div class="d-flex justify-content-between mb-3">
            <h4>Crear Pedido a Proveedor</h4>
            <div>
                <a href="{{ route('web.provider-orders.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar como Borrador</button>
            </div>
        </div>

        @include('admin.provider-order.partials._form', ['formData' => $formData])
    </form>
@endsection

@push('scripts')
    @vite('resources/js/modules/provider-order/form.js')
@endpush
