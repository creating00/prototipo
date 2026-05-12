@extends('layouts.app')

@section('page-title', 'Editar Banco')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12" style="max-width: 50%;">
            <x-adminlte.form action="{{ route('web.banks.update', $bank->id) }}" method="PUT" title="Editar Banco"
                submit-text="Actualizar Banco" submitting-text="Actualizando banco...">

                @include('admin.bank.partials._form', [
                    'bank' => $bank,
                ])

                <x-slot name="footer">
                    <x-adminlte.form-footer cancelRoute="{{ route('web.banks.index') }}"
                        submitText="Actualizar Banco" />
                </x-slot>

            </x-adminlte.form>
        </div>
    </div>
@endsection
