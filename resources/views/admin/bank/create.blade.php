@extends('layouts.app')

@section('page-title', 'Crear Banco')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12" style="max-width: 50%;">
            <x-adminlte.form action="{{ route('web.banks.store') }}" title="Crear Banco" submit-text="Guardar Banco"
                submitting-text="Creando banco...">

                @include('admin.bank.partials._form', [
                    'bank' => null,
                ])

                <x-slot name="footer">
                    <x-adminlte.form-footer cancelRoute="{{ route('web.banks.index') }}"
                        submitText="Guardar" />
                </x-slot>

            </x-adminlte.form>
        </div>
    </div>
@endsection
