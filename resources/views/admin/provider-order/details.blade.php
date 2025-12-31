@extends('layouts.app')

@section('page-title', 'Detalle de Orden de Compra #' . $order->id)

@section('content')
    <div class="container-fluid">
        <x-adminlte.alert-manager />

        <div class="card mb-4">
            <div class="card-header bg-navy">
                <h5 class="mb-0 text-black">
                    <i class="fas fa-file-invoice-dollar me-2"></i> Orden de Compra N°
                    {{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
                </h5>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    {{-- Información del Proveedor --}}
                    <div class="col-md-3">
                        <strong><i class="fas fa-truck me-1"></i> Proveedor</strong><br>
                        <span class="text-primary">{{ $order->provider->business_name }}</span><br>
                        <small class="text-muted">{{ $order->provider->cuit }}</small>
                    </div>

                    {{-- Sucursal Destino (Donde entra la mercadería) --}}
                    <div class="col-md-3">
                        <strong><i class="fas fa-store me-1"></i> Sucursal Destino</strong><br>
                        {{ $order->branch->name }}
                    </div>

                    {{-- Estado --}}
                    <div class="col-md-3">
                        <strong><i class="fas fa-info-circle me-1"></i> Estado</strong><br>
                        {{-- Nota: Asegúrate de tener un método badgeClass en tu Enum ProviderOrderStatus --}}
                        <span
                            class="badge {{ method_exists($order->status, 'badgeClass') ? $order->status->badgeClass() : 'bg-secondary' }}">
                            {{ $order->status->label() }}
                        </span>
                    </div>

                    <div class="col-md-3">
                        <strong><i class="fas fa-calendar-alt me-1"></i> Fecha de Orden</strong><br>
                        {{ $order->order_date->format('d/m/Y') }}
                    </div>

                    <div class="col-md-3">
                        <strong><i class="fas fa-clock me-1"></i> Entrega Estimada</strong><br>
                        <span
                            class="{{ $order->expected_delivery_date?->isPast() && $order->status->value < 4 ? 'text-danger fw-bold' : '' }}">
                            {{ $order->expected_delivery_date ? $order->expected_delivery_date->format('d/m/Y') : 'No definida' }}
                        </span>
                    </div>

                    <div class="col-md-3">
                        <strong><i class="fas fa-check-double me-1"></i> Fecha de Recepción</strong><br>
                        {{ $order->received_date ? $order->received_date->format('d/m/Y') : 'Pendiente de recibo' }}
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <div class="float-right text-right">
                    @php
                        // Agrupamos los totales por moneda para no sumar peras con manzanas
                        $totalsByCurrency = $order->items->groupBy('currency')->map(function ($group) {
                            return [
                                'symbol' => $group->first()->currency->symbol(),
                                'amount' => $group->sum(fn($i) => $i->quantity * $i->unit_cost),
                            ];
                        });
                    @endphp

                    <h5 class="text-muted mb-1">Resumen de Totales:</h5>
                    @foreach ($totalsByCurrency as $total)
                        <h4 class="mb-0">
                            Total {{ $loop->first ? '' : '&' }}
                            <strong>{{ $total['symbol'] }} {{ number_format($total['amount'], 2, ',', '.') }}</strong>
                        </h4>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Tabla de Productos --}}
        <x-adminlte.data-table tableId="provider-order-items-table" title="Productos Solicitados" :headers="$headers"
            :rowData="$rowData" :hiddenFields="$hiddenFields" :withActions="false">
        </x-adminlte.data-table>

        <div class="mt-3 d-flex justify-content-between">
            <a href="{{ route('web.provider-orders.index') }}" class="btn btn-default">
                <i class="fas fa-arrow-left me-1"></i> Volver al listado
            </a>

            <div>
                @if ($order->status === \App\Enums\ProviderOrderStatus::SENT)
                    <form action="{{ route('web.provider-orders.receive', $order->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success"
                            onclick="return confirm('¿Confirmar recepción de mercadería? Esto actualizará stock y precios.')">
                            <i class="fas fa-box-open me-1"></i> Marcar como Recibido
                        </button>
                    </form>
                @endif

                <button class="btn btn-info btn-print-order" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Imprimir OC
                </button>
            </div>
        </div>
    </div>
@endsection
