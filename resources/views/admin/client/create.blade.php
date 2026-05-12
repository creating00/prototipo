@extends('layouts.app')

@section('page-title', 'Crear Cliente')

@section('content')
    <x-adminlte.form action="{{ route('web.clients.store') }}" title="Crear Cliente" submit-text="Guardar Cliente"
        submitting-text="Creando cliente...">
        @include('admin.client.partials._form', [
            'client' => null,
        ])
    </x-adminlte.form>
@endsection

@push('scripts')
    @vite('resources/js/modules/clients/create.js')
@endpush
