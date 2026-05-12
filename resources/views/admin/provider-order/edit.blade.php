@extends('layouts.app')

@section('page-title', 'Editar Orden de Compra')

@section('content')
    <x-adminlte.alert-manager />
    <form action="{{ route('web.provider-orders.update', $order->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="d-flex justify-content-between mb-3">
            <h4>Editando Orden #{{ $order->id }}</h4>
            <div>
                <a href="{{ route('web.provider-orders.index') }}" class="btn btn-secondary">Volver</a>
                @if ($order->status === \App\Enums\ProviderOrderStatus::PENDING)
                    <button type="submit" class="btn btn-success">Actualizar Cambios</button>
                @endif
            </div>
        </div>

        @include('admin.provider-order.partials._form', [
            'formData' => $formData,
            'order' => $order,
        ])
    </form>
@endsection

@push('scripts')
    @vite('resources/js/modules/provider-order/form.js')
@endpush