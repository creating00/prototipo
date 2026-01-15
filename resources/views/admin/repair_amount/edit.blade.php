@extends('layouts.app')

@section('page-title', 'Editar Monto de Reparación')

@section('content')
    <x-adminlte.form action="{{ route('web.repair-amounts.update', $repairAmount->id) }}" method="PUT"
        title="Editar Monto de Reparación" submit-text="Actualizar Monto" submitting-text="Actualizando monto...">
        @include('admin.repair_amount.partials._form', [
            'repairAmount' => $repairAmount,
            'repairTypes' => $repairTypes,
        ])
    </x-adminlte.form>
@endsection
