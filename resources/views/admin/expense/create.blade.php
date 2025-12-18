@extends('layouts.app')

@section('page-title', 'Registrar Gasto')

@section('content')
    <x-admin-lte.alert-manager />

    <x-admin-lte.form action="{{ route('web.expenses.store') }}" method="POST" title="Registrar Nuevo Gasto"
        submit-text="Guardar Gasto" submitting-text="Registrando gasto...">
        @include('admin.expense.partials._form', [
            'formData' => $formData,
        ])
    </x-admin-lte.form>

    {{-- Modales auxiliares --}}
    @include('admin.branch.partials._modal-create')
    @include('admin.expense_type.partials._modal-create')
@endsection

@push('scripts')
    @vite('resources/js/modules/expenses/create.js')
@endpush
