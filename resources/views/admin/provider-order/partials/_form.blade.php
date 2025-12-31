@props(['formData', 'order' => null])

@php
    use App\Enums\PriceType;
    use App\Enums\CurrencyType;
@endphp

<div class="row">
    {{-- Datos de Cabecera --}}
    <div class="col-md-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Información del Pedido</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <x-adminlte.select-with-action name="provider_id" label="Proveedor" :options="$formData->providers->pluck('display_name', 'id')->toArray()"
                            :value="old('provider_id', $order?->provider_id)" buttonId="btn-new-provider" required />
                    </div>

                    <div class="col-12">
                        <x-bootstrap.compact-input name="order_date" type="date" label="Fecha de Orden"
                            :value="old(
                                'order_date',
                                $order?->order_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
                            )" />
                    </div>

                    <div class="col-12">
                        <x-bootstrap.compact-input name="expected_delivery_date" type="date"
                            label="Fecha Entrega Estimada" :value="old('expected_delivery_date', $order?->expected_delivery_date?->format('Y-m-d'))" />
                    </div>

                    <div class="col-12">
                        <x-adminlte.select name="status" label="Estado del Pedido" :options="$formData->statusOptions" :value="old(
                            'status',
                            $order?->status->value ?? \App\Enums\ProviderOrderStatus::PENDING->value,
                        )"
                            required />
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detalle de Productos --}}
    <div class="col-md-8">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Productos / Items</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-success" id="btn-add-item" disabled>
                        <i class="fas fa-plus"></i> Agregar Producto
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-valign-middle" id="items-table">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 35%">Producto</th>
                            <th style="width: 10%">Cant.</th>
                            <th style="width: 20%">Costo Unit.</th>
                            <th style="width: 15%" class="text-end">Subtotal</th>
                            <th style="width: 5%"></th>
                        </tr>
                    </thead>
                    <tbody id="items-container">
                        @if ($order)
                            @foreach ($order->items as $index => $item)
                                @include('admin.provider-order.partials._item_row', [
                                    'index' => $index,
                                    'item' => $item,
                                ])
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="bg-light">
                            <th colspan="3" class="text-right">TOTAL ESTIMADO:</th>
                            <th id="order-total" class="text-end">$ 0.00</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<template id="item-row-template">
    @include('admin.provider-order.partials._item_row', [
        'index' => '__INDEX__',
        'item' => null,
    ])
</template>
