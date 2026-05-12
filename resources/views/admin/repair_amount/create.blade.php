@extends('layouts.app')

@section('page-title', 'Crear Monto de Reparación')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12" style="max-width: 80%;">
            <x-adminlte.alert-manager />
            <x-adminlte.form action="{{ route('web.repair-amounts.store') }}" title="Crear Monto de Reparación"
                submit-text="Guardar Monto" submitting-text="Creando monto...">
                @include('admin.repair_amount.partials._form', [
                    'repairAmount' => null,
                    'repairTypes' => $repairTypes,
                ])
            </x-adminlte.form>
        </div>
    </div>
@endsection
