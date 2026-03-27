@extends('layouts.app')

@section('page-title', 'Banners y Promociones')

@section('content')
    <div class="container-fluid">
        <x-adminlte.alert-manager />

        {{-- Formulario oculto para la subida directa --}}
        <form id="direct-upload-form" action="{{ route('web.promotions.store') }}" method="POST" enctype="multipart/form-data"
            style="display: none;">
            @csrf
            <input type="file" id="direct-upload-input" name="image" accept="image/*">
        </form>

        <x-adminlte.data-table tableId="promotion-images-table" title="Gestión de Banners" :headers="$headers"
            :rowData="$rowData" :hiddenFields="$hiddenFields" withActions="true">

            @canResource('promotions.update')
            <x-adminlte.button color="warning" size="sm" icon="fas fa-eye-slash"
                class="me-1 btn-toggle-status btn-deactivate" data-status="1" />
            <x-adminlte.button color="success" size="sm" icon="fas fa-eye" class="me-1 btn-toggle-status btn-activate"
                data-status="0" />
            @endcanResource

            @canResource('promotions.delete')
            <x-adminlte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete" />
            @endcanResource

            <x-slot name="headerButtons">
                @canResource('promotions.create')
                <x-adminlte.button color="primary" icon="fas fa-upload" class="me-1 btn-header-new">
                    Subir Banner
                </x-adminlte.button>
                @endcanResource
            </x-slot>
        </x-adminlte.data-table>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/promotion_images/index.js')
@endpush
