@extends('layouts.app')

@section('page-title', 'Editar Cuenta Bancaria')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12" style="max-width: 70%;">
            <x-adminlte.form action="{{ route('web.bank-accounts.update', $formData->bankAccount->id) }}" method="PUT"
                title="Editar Cuenta Bancaria" submit-text="Actualizar Cuenta"
                submitting-text="Actualizando cuenta bancaria...">

                @include('admin.bank_account.partials._form', [
                    'formData' => $formData,
                ])

                <x-slot name="footer">
                    <x-adminlte.form-footer cancelRoute="{{ route('web.bank-accounts.index') }}"
                        submitText="Actualizar Cuenta" />
                </x-slot>

            </x-adminlte.form>
        </div>
    </div>
@endsection
