@extends('layouts.app')

@section('page-title', 'Detalle del Pedido #' . $order->id)

@section('content')
    <div class="container-fluid">
        <x-adminlte.alert-manager />

        <div class="card mb-4">
            <div class="card-header bg-navy">
                <h5 class="mb-0 text-black">
                    <i class="fas fa-shopping-cart me-2"></i> Pedido N° {{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
                </h5>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    {{-- Información del Cliente --}}
                    <div class="col-md-3">
                        <strong><i class="fas fa-user me-1"></i> Cliente / Destino</strong><br>
                        @if ($order->customer_type === \App\Models\Branch::class)
                            <span class="badge-custom badge-custom-pastel-blue">Sucursal: {{ $order->customer->name }}</span>
                        @else
                            {{ $order->customer->full_name ?? ($order->customer->business_name ?? 'N/A') }}
                        @endif
                    </div>

                    <div class="col-md-3">
                        <strong><i class="fas fa-store me-1"></i> Sucursal Origen</strong><br>
                        {{ $order->branch->name }}
                    </div>

                    {{-- ESTADO: Aquí usamos el Enum correctamente --}}
                    <div class="col-md-3">
                        <strong><i class="fas fa-info-circle me-1"></i> Estado</strong><br>
                        <span class="badge {{ $order->status->badgeClass() }}">
                            {{ $order->status->label() }}
                        </span>
                    </div>

                    <div class="col-md-3">
                        <strong><i class="fas fa-calendar-alt me-1"></i> Fecha</strong><br>
                        {{ $order->created_at->format('d/m/Y H:i') }}
                    </div>

                    <div class="col-md-3">
                        <strong><i class="fas fa-user-tie me-1"></i> Vendedor</strong><br>
                        {{ $order->user->name ?? 'Sistema' }}
                    </div>

                    <div class="col-md-3">
                        <strong><i class="fas fa-laptop me-1"></i> Canal</strong><br>
                        {{ $order->source == 1 ? 'Backoffice' : 'Ecommerce' }}
                    </div>

                    <div class="col-md-6">
                        <strong><i class="fas fa-sticky-note me-1"></i> Notas</strong><br>
                        <small class="text-muted">{{ $order->notes ?? 'Sin observaciones adicionales' }}</small>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <div class="float-right">
                    <h4 class="mb-0">Total: <strong>${{ number_format($order->total_amount, 2, ',', '.') }}</strong></h4>
                </div>
            </div>
        </div>

        <x-adminlte.data-table tableId="order-items-table" title="Productos en el Pedido" :headers="$headers"
            :rowData="$rowData" :hiddenFields="$hiddenFields" :withActions="false">
        </x-adminlte.data-table>

        <div class="mt-3 d-flex justify-content-between">
            <a href="{{ $backUrl ?? route('web.orders.index') }}" class="btn btn-default">
                <i class="fas fa-arrow-left me-1"></i> Volver al listado
            </a>

            <button class="btn btn-info btn-print-order">
                <i class="fas fa-print me-1"></i> Imprimir Pedido
            </button>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Si necesitas JS específico para esta vista, como imprimir el ticket --}}
    @vite('resources/js/modules/orders/details.js')
@endpush
