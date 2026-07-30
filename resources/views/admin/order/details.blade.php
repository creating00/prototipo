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

                    {{-- ESTADO --}}
                    <div class="col-md-3">
                        <strong><i class="fas fa-info-circle me-1"></i> Estado del Pedido</strong><br>
                        <span class="badge {{ $order->status->badgeClass() }}">
                            {{ $order->status->label() }}
                        </span>
                        @if ($order->is_stock_sent)
                            <span class="badge bg-success ms-1"><i class="fas fa-box-check me-1"></i> Enviado al Stock</span>
                        @endif
                    </div>

                    {{-- ESTADO DE PAGO --}}
                    <div class="col-md-4">
                        <strong><i class="fas fa-money-bill-wave me-1"></i> Estado de Pago</strong><br>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span id="payment-status-badge" class="{{ $order->payment_status_badge_class }}">
                                {{ $order->payment_status_label }}
                            </span>
                            <select id="select-payment-status" class="form-select form-select-sm w-auto py-0 px-2">
                                <option value="2" {{ (int)$order->payment_status === 2 ? 'selected' : '' }}>Pendiente</option>
                                <option value="1" {{ (int)$order->payment_status === 1 ? 'selected' : '' }}>Pagado</option>
                            </select>
                            <button type="button" id="btn-save-payment-status" class="btn btn-sm btn-outline-success py-0 px-2" title="Guardar Estado de Pago">
                                <i class="fas fa-save me-1"></i> Guardar
                            </button>
                        </div>
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

        @php
            $userBranchId = auth()->user()?->branch_id;
            $isManual = $order->source === \App\Enums\OrderSource::Manual;
            $isPurchaser = $isManual || ($order->isInterBranch() && $userBranchId && ((int)$order->customer_id === (int)$userBranchId));
            $isSupplier = !$isManual && (!$order->isInterBranch() || !$userBranchId || ((int)$order->branch_id === (int)$userBranchId));
        @endphp

        <div class="mt-3 d-flex justify-content-between">
            <a href="{{ $backUrl ?? route('web.orders.index') }}" class="btn btn-default">
                <i class="fas fa-arrow-left me-1"></i> Volver al listado
            </a>

            <div class="d-flex gap-2">
                {{-- Botón Enviar al Stock (Para sucursal compradora o pedido manual) --}}
                @if ($isPurchaser && !$order->is_stock_sent && $order->status !== \App\Enums\OrderStatus::Cancelled)
                    <button type="button" id="btn-send-to-stock" class="btn btn-primary btn-sm">
                        <i class="fas fa-boxes me-1"></i> Enviar al Stock
                    </button>
                @endif

                @if ($order->canBeEdited())
                    <a href="{{ route('web.orders.edit', $order->id) }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit me-1"></i> Editar Pedido
                    </a>
                @endif

                {{-- Caso 1: Si ya está convertido --}}
                @if ($order->status === \App\Enums\OrderStatus::ConvertedToSale && $order->sale_id)
                    <x-adminlte.button color="info" size="sm" icon="fas fa-print" class="btn-print"
                        title="Imprimir Comprobante" data-id="{{ $order->id }}"
                        data-sale_id="{{ $order->sale_id ?? '' }}">
                        Imprimir Comprobante
                    </x-adminlte.button>
                @elseif ($isSupplier && $order->status !== \App\Enums\OrderStatus::Cancelled)
                    <x-adminlte.button color="success" size="sm" icon="fas fa-file-invoice-dollar"
                        class="btn-convert" title="Convertir a Venta" data-id="{{ $order->id }}"
                        data-totals_json="{{ json_encode($order->totals) }}"
                        data-customer_name="{{ $order->customer_name }}" data-customer_type="{{ $order->customer_type }}"
                        data-exchange_rate="{{ $order->exchange_rate }}" data-api-url="{{ route('web.orders.index') }}">
                        Convertir a Venta
                    </x-adminlte.button>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const btnSendStock = document.getElementById('btn-send-to-stock');
                if (btnSendStock) {
                    btnSendStock.addEventListener('click', async () => {
                        if (!confirm('¿Desea enviar este pedido al stock? Se incrementará el inventario según las cantidades cargadas y no se podrá modificar el registro del pedido.')) {
                            return;
                        }

                        btnSendStock.disabled = true;
                        try {
                            const response = await fetch('{{ route('web.orders.send-to-stock', $order->id) }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                }
                            });
                            const data = await response.json();
                            if (response.ok && data.success) {
                                alert(data.message);
                                window.location.reload();
                            } else {
                                alert(data.message || 'Error al enviar al stock.');
                                btnSendStock.disabled = false;
                            }
                        } catch (err) {
                            console.error(err);
                            alert('Error de conexión al enviar al stock.');
                            btnSendStock.disabled = false;
                        }
                    });
                }

                const selectPayment = document.getElementById('select-payment-status');
                const btnSavePayment = document.getElementById('btn-save-payment-status');

                async function savePaymentStatus() {
                    if (!selectPayment) return;
                    const newStatus = selectPayment.value;
                    if (btnSavePayment) btnSavePayment.disabled = true;

                    try {
                        const response = await fetch('{{ route('web.orders.update-payment-status', $order->id) }}', {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ payment_status: newStatus })
                        });
                        const data = await response.json();
                        if (response.ok && data.success) {
                            const badge = document.getElementById('payment-status-badge');
                            if (badge) {
                                badge.className = data.payment_status_badge;
                                badge.textContent = data.payment_status_label;
                            }
                            alert('Estado de pago guardado correctamente: ' + data.payment_status_label);
                        } else {
                            alert(data.message || 'Error al actualizar el estado de pago.');
                        }
                    } catch (err) {
                        console.error(err);
                        alert('Error de conexión al actualizar el estado de pago.');
                    } finally {
                        if (btnSavePayment) btnSavePayment.disabled = false;
                    }
                }

                if (btnSavePayment) {
                    btnSavePayment.addEventListener('click', savePaymentStatus);
                }
                if (selectPayment) {
                    selectPayment.addEventListener('change', savePaymentStatus);
                }
            });
        </script>
    @endpush
    @include('admin.order.partials._convert_to_sale_modal')
    @include('admin.sales.partials._modal-print')
@endsection

@push('scripts')
    {{-- Si necesitas JS específico para esta vista, como imprimir el ticket --}}
    @vite('resources/js/modules/orders/details.js')
@endpush
