@extends('layouts.app')

@section('page-title', 'Crear Cuenta Bancaria')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12" style="max-width: 70%;">
            <x-adminlte.form action="{{ route('web.bank-accounts.store') }}" title="Crear Cuenta Bancaria"
                submit-text="Guardar Cuenta" submitting-text="Creando cuenta bancaria...">

                @include('admin.bank_account.partials._form', [
                    'formData' => $formData,
                ])

                <x-slot name="footer">
                    <x-adminlte.form-footer cancelRoute="{{ route('web.bank-accounts.index') }}"
                        submitText="Guardar Cuenta" />
                </x-slot>

            </x-adminlte.form>
        </div>
    </div>
@endsection
