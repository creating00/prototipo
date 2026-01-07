@extends('layouts.app')

@section('page-title', 'Detalle de Venta #' . ($sale->internal_number ?? $sale->id))

@section('content')
    <div class="container-fluid">
        <x-adminlte.alert-manager />

        <div class="card mb-4">
            <div class="card-header bg-navy">
                <h5 class="mb-0 text-white">
                    <i class="fas fa-receipt me-2"></i> Venta N°
                    {{ $sale->internal_number ?? str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}
                </h5>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    {{-- Información del Cliente --}}
                    <div class="col-md-3">
                        <strong><i class="fas fa-user me-1"></i> Cliente</strong><br>
                        @if ($sale->isInterBranch())
                            <span class="badge-custom badge-custom-pastel-blue">Sucursal: {{ $sale->customer->name }}</span>
                        @else
                            {{ $sale->customer->full_name ?? ($sale->customer->business_name ?? 'Cliente General') }}
                        @endif
                    </div>

                    <div class="col-md-3">
                        <strong><i class="fas fa-store me-1"></i> Sucursal</strong><br>
                        {{ $sale->branch->name }}
                    </div>

                    <div class="col-md-3">
                        <strong><i class="fas fa-info-circle me-1"></i> Estado</strong><br>
                        <span class="badge {{ $sale->status->badgeClass() }}">
                            {{ $sale->status->label() }}
                        </span>
                    </div>

                    <div class="col-md-3">
                        <strong><i class="fas fa-calendar-alt me-1"></i> Fecha</strong><br>
                        {{ $sale->created_at->format('d/m/Y H:i') }}
                    </div>

                    <div class="col-md-3">
                        <strong><i class="fas fa-user-tie me-1"></i> Vendedor</strong><br>
                        {{ $sale->user->name ?? 'Sistema' }}
                    </div>

                    <div class="col-md-3">
                        <strong><i class="fas fa-tag me-1"></i> Tipo</strong><br>
                        {{ $sale->sale_type->label() }}
                    </div>

                    <div class="col-md-6">
                        <strong><i class="fas fa-sticky-note me-1"></i> Notas</strong><br>
                        <small class="text-muted">{{ $sale->notes ?? 'Sin observaciones' }}</small>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        @if ($sale->hasDiscount())
                            <span class="text-danger fw-bold">
                                <i class="fas fa-arrow-down me-1"></i> Descuento:
                                -${{ number_format($sale->discount_amount, 2, ',', '.') }}
                            </span>
                        @endif
                    </div>
                    <div class="col-md-6 d-flex justify-content-end">
                        <div class="bg-light p-3 rounded border" style="min-width: 250px;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted me-3">Total a Pagar:</span>
                                <h4 class="mb-0 text-navy">
                                    <strong>${{ number_format($sale->total_amount, 2, ',', '.') }}</strong>
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <x-adminlte.data-table tableId="sale-items-table" title="Productos Vendidos" :headers="$headers" :rowData="$rowData"
            :hiddenFields="$hiddenFields" :withActions="false">
        </x-adminlte.data-table>

        <div class="mt-3 d-flex justify-content-between">
            <a href="{{ $backUrl ?? route('web.sales.index') }}" class="btn btn-default">
                <i class="fas fa-arrow-left me-1"></i> Volver al listado
            </a>

            <div class="btn-group">
                {{-- En el grupo de botones --}}
                <button class="btn btn-info btn-print-sale" data-id="{{ $sale->id }}"
                    data-base-url="{{ route('web.sales.index') }}">
                    <i class="fas fa-print me-1"></i> Imprimir Comprobante
                </button>
                <a href="https://wa.me/?text={{ urlencode($sale->generateWhatsAppMessage()) }}" target="_blank"
                    class="btn btn-success">
                    <i class="fab fa-whatsapp me-1"></i> Enviar por WhatsApp
                </a>
            </div>
        </div>
    </div>
    @include('admin.sales.partials._modal-print')
@endsection

@push('scripts')
    @vite('resources/js/modules/sales/details.js')
@endpush
