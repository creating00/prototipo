@extends('layouts.app')

@section('page-title', 'Editar Gasto')

@section('content')
    <x-adminlte.alert-manager />

    <x-adminlte.form action="{{ route('web.expenses.update', $formData->expense) }}" method="POST" title="Editar Gasto"
        submit-text="Actualizar Gasto" submitting-text="Actualizando gasto...">
        @method('PUT')
        @include('admin.expense.partials._form', [
            'formData' => $formData,
        ])
    </x-adminlte.form>

    {{-- Modales auxiliares --}}
    @include('admin.branch.partials._modal-create')
    {{--  @include('admin.expense_type.partials._modal-create') --}}
@endsection

@push('scripts')
    @vite('resources/js/modules/expenses/edit.js')
@endpush
