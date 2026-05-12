@extends('layouts.app')

@section('page-title', 'Crear Descuento')

@section('content')
    <div class="container-fluid">
        <x-adminlte.form action="{{ route('web.discounts.store') }}" title="Nuevo Descuento / Promoción"
            submit-text="Guardar Descuento" submitting-text="Procesando..." icon="fas fa-percentage">
            @include('admin.discount.partials._form', [
                'formData' => (object) [
                    'discount' => null,
                ],
            ])
        </x-adminlte.form>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/discounts/form.js')
@endpush
