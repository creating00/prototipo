@extends('layouts.app')

@section('page-title', 'Crear Usuario')

@section('content')
    {{-- Manejo de alertas de sesión --}}
    <x-admin-lte.alert-manager />

    {{-- Formulario principal de creación --}}
    <x-admin-lte.form action="{{ route('web.users.store') }}" method="POST" title="Registrar Nuevo Usuario"
        submit-text="Guardar Usuario" submitting-text="Registrando..." enctype="multipart/form-data">

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
