@extends('layouts.app')

@section('page-title', 'Editar Promoción')

@section('content')
    <x-adminlte.alert-manager />

    <x-adminlte.form action="{{ route('web.promotions.update', $formData->promotion->id) }}" method="PUT"
        title="Editar Promoción / Banner: {{ $formData->promotion->title }}" submit-text="Actualizar Promoción"
        submitting-text="Actualizando...">

        @include('admin.promotion.partials._form', [
            'formData' => $formData,
        ])

    </x-adminlte.form>

    {{-- Mantenemos la posibilidad de incluir modales de previsualización --}}
    {{-- @include('admin.promotion.partials._modal-preview') --}}
@endsection

@push('scripts')
    {{-- Reutilizamos o extendemos la lógica de JS para el manejo de botones --}}
    @vite('resources/js/modules/promotions/edit.js')
@endpush
