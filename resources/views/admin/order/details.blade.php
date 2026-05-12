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
                        {{ $order->source->label() }}
                    </div>

                    <div class="col-md-6">
                        <strong><i class="fas fa-sticky-note me-1"></i> Notas</strong><br>
                        <small class="text-muted">{{ $order->notes ?? 'Sin observaciones adicionales' }}</small>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <div class="row align-items-center">

                    {{-- Subtotales --}}
                    <div class="col-md-6">
                        <div class="d-flex gap-3 text-muted small">
                            @foreach ($order->subtotals as $formatted)
                                <span>{{ $formatted }}</span>
                            @endforeach
                        </div>

                        @if ($order->exchange_rate)
                            <span class="badge badge-custom badge-custom-gradient-arctic">
                                Cotización: $ {{ number_format($order->exchange_rate, 2, ',', '.') }}
                            </span>
                        @endif
                    </div>

                    {{-- Totales finales --}}
                    <div class="col-md-6 text-end">
                        <div class="d-inline-block">
                            @foreach ($order->formatted_totals as $currency => $formatted)
                                <h4 class="mb-1">
                                    Total {{ $currency }}:
                                    <strong>{{ $formatted }}</strong>
                                </h4>
                            @endforeach
                        </div>
                    </div>

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

            <div>
                {{-- Caso 1: Si ya está convertido, mostramos el botón de imprimir --}}
                @if ($order->status === \App\Enums\OrderStatus::ConvertedToSale && $order->sale_id)
                    <x-adminlte.button color="info" size="sm" icon="fas fa-print" class="btn-print"
                        title="Imprimir Comprobante" data-id="{{ $order->id }}" {{-- Forzamos a que sea un string o null para que el JS no se rompa --}}
                        data-sale_id="{{ $order->sale_id ?? '' }}">
                        Imprimir Comprobante
                    </x-adminlte.button>

                    {{-- Caso 2: Si NO está cancelado y NO está convertido, mostramos el de convertir --}}
                @elseif ($order->status !== \App\Enums\OrderStatus::Cancelled)
                    <x-adminlte.button color="success" size="sm" icon="fas fa-file-invoice-dollar"
                        class="me-1 btn-convert" title="Convertir a Venta" data-id="{{ $order->id }}"
                        data-totals_json="{{ json_encode($order->totals) }}"
                        data-customer_name="{{ $order->customer_name }}" data-customer_type="{{ $order->customer_type }}"
                        data-exchange_rate="{{ $order->exchange_rate }}" data-api-url="{{ route('web.orders.index') }}">
                        Convertir a Venta
                    </x-adminlte.button>
                @endif

                {{-- Caso 3: Si está Cancelled, no entra en ninguno de los anteriores y no muestra nada --}}
            </div>
        </div>
    </div>
    @include('admin.order.partials._convert_to_sale_modal')
    @include('admin.sales.partials._modal-print')
@endsection

@push('scripts')
    {{-- Si necesitas JS específico para esta vista, como imprimir el ticket --}}
    @vite('resources/js/modules/orders/details.js')
@endpush
