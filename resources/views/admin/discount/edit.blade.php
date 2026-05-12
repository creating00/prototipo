@extends('layouts.app')

@section('page-title', 'Editar Descuento')

@section('content')
    <div class="container-fluid">
        <x-adminlte.form method="PUT" action="{{ route('web.discounts.update', $discount->id) }}"
            title="Editar Descuento: {{ $discount->name }}" submit-text="Actualizar Descuento"
            submitting-text="Guardando cambios..." icon="fas fa-edit">
            @include('admin.discount.partials._form', [
                'formData' => (object) [
                    'discount' => $discount,
                ],
            ])
        </x-adminlte.form>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/discounts/form.js')
@endpush
