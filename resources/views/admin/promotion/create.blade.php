@extends('layouts.app')

@section('page-title', 'Crear Promoción')

@section('content')
    <x-adminlte.alert-manager />

    <x-adminlte.form action="{{ route('web.promotions.store') }}" method="POST" title="Nueva Promoción / Banner"
        submit-text="Guardar Promoción" submitting-text="Guardando...">

        @include('admin.promotion.partials._form', [
            'formData' => $formData,
        ])

    </x-adminlte.form>

    {{-- Modales auxiliares si fueran necesarios --}}
    {{-- @include('admin.promotion.partials._modal-preview') --}}
@endsection

@push('scripts')
    @vite('resources/js/modules/promotions/create.js')
@endpush
