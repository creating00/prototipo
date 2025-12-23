@extends('layouts.app')

@section('page-title', 'Editar Usuario')

@section('content')
    {{-- Manejo de alertas de sesión --}}
    <x-admin-lte.alert-manager />

    {{-- Formulario principal de creación --}}
    <x-admin-lte.form action="{{ route('web.users.update', $formData->user->id) }}" method="PUT" title="Editar Usuario"
        submit-text="Actualizar Usuario" submitting-text="Actualizando..." enctype="multipart/form-data">

        @include('admin.user.partials._form', [
            'formData' => $formData,
        ])
    </x-admin-lte.form>

    {{-- Modales para creación rápida desde selectores --}}
    @include('admin.branch.partials._modal-create')

@endsection

@push('scripts')
    {{-- Lógica específica para el módulo de usuarios --}}
    @vite('resources/js/modules/users/create.js')
@endpush
