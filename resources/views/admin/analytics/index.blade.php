@extends('layouts.app')

@section('page-title', 'Panel de Control')

@push('styles')
    @vite('resources/css/modules/branches/branches-styles.css')
@endpush

@section('content')
    {{-- BLOQUE DE FILTROS REFACTORIZADO --}}
    <x-adminlte.card title="Filtros de Reporte" type="white">
        <x-slot:tools>
            <a href="{{ route('web.analytics.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-sync-alt mr-1"></i> Restablecer
            </a>
        </x-slot:tools>

        <form method="GET" action="{{ route('web.analytics.index') }}">
            <div class="row">
                {{-- Filtro de Sucursal --}}
                <div class="col-md-3">
                    <div class="compact-select-wrapper">
                        <label class="compact-select-label">Sucursal</label>
                        <x-adminlte.select name="branch_id" :options="$branches" :value="$currentFilters['branch_id']" :showPlaceholder="false"
                            onchange="this.form.submit()" />
                    </div>
                </div>

                {{-- Filtro de Categoría --}}
                <div class="col-md-3">
                    <div class="compact-select-wrapper">
                        <label class="compact-select-label">Categoría</label>
                        <x-adminlte.select name="category_id" :options="$categories" :value="$currentFilters['category_id']"
                            placeholder="Todas las categorías" onchange="this.form.submit()" />
                    </div>
                </div>

                {{-- Filtro de Fecha Desde --}}
                <div class="col-md-3">
                    <x-bootstrap.compact-input id="start_date" name="start_date" type="date" label="Desde"
                        value="{{ $currentFilters['start_date'] }}" onchange="this.form.submit()" />
                </div>

                {{-- Filtro de Fecha Hasta --}}
                <div class="col-md-3">
                    <x-bootstrap.compact-input id="end_date" name="end_date" type="date" label="Hasta"
                        value="{{ $currentFilters['end_date'] }}" onchange="this.form.submit()" />
                </div>
            </div>
        </form>
    </x-adminlte.card>

    <h6 class="text-muted text-uppercase mb-2">Actividad</h6>
    <div class="row mb-4">
        @foreach ($infoboxes as $box)
            <div class="col-12 col-sm-6 col-md-4">
                <x-adminlte.infobox icon="{{ $box['icon'] }}" color="{{ $box['color'] }}" text="{{ $box['text'] }}"
                    number="{{ $box['number'] ?? 0 }}" prefix="{{ $box['prefix'] ?? null }}"
                    secondary-number="{{ $box['secondaryNumber'] ?? null }}"
                    secondary-suffix="{{ $box['secondarySuffix'] ?? null }}" />
            </div>
        @endforeach
    </div>

    {{-- <h6 class="text-muted text-uppercase mb-2">Gastos</h6> --}}
    <div class="row mb-4">
        @foreach ($expenseBoxes as $box)
            <div class="col-12 col-sm-6 col-md-4">
                <x-adminlte.infobox icon="{{ $box['icon'] }}" color="{{ $box['color'] }}" text="{{ $box['text'] }}"
                    number="{{ $box['number'] ?? 0 }}" prefix="{{ $box['prefix'] ?? null }}" />
            </div>
        @endforeach
    </div>

    {{-- <h6 class="text-muted text-uppercase mb-2">Resultados</h6> --}}
    <div class="row mb-5 justify-content-center">
        @foreach ($resultBoxes as $box)
            <div class="col-12 col-sm-6 col-md-4">
                <x-adminlte.infobox icon="{{ $box['icon'] }}" color="{{ $box['color'] }}" text="{{ $box['text'] }}"
                    number="{{ $box['number'] ?? 0 }}" prefix="{{ $box['prefix'] ?? null }}" />
            </div>
        @endforeach
    </div>

    {{-- RECAUDACIÓN Y GANANCIAS --}}
    <div class="row mb-4">
        <div class="col-lg-8">
            <x-adminlte.card title="Recaudación mensual" type="primary" :showTools="true">
                <x-adminlte.chart id="revenueChart" height="320" />
            </x-adminlte.card>
        </div>
        <div class="col-lg-4">
            <x-adminlte.card title="Ganancias anuales" type="success" :showTools="true">
                <x-adminlte.chart id="profitChart" height="320" />
            </x-adminlte.card>
        </div>
    </div>

    {{-- PRODUCTOS, CLIENTES Y STOCK --}}
    <div class="row">
        <div class="col-lg-4">
            <x-adminlte.card title="Top Productos" type="white" :showTools="true">
                @if ($products->isEmpty())
                    <x-adminlte.empty-state />
                @else
                    <x-adminlte.table-analytic>
                        <x-slot name="thead">
                            <tr>
                                <th>Producto</th>
                                <th class="text-end">Unidades</th>
                            </tr>
                        </x-slot>
                        @foreach ($products as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td class="text-end">{{ $product->units }}</td>
                            </tr>
                        @endforeach
                    </x-adminlte.table-analytic>
                @endif
            </x-adminlte.card>
        </div>

        <div class="col-lg-4">
            <x-adminlte.card title="Mejores clientes" type="white" :showTools="true">
                @if ($clients->isEmpty())
                    <x-adminlte.empty-state />
                @else
                    <x-adminlte.table-analytic>
                        <x-slot name="thead">
                            <tr>
                                <th>Cliente</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </x-slot>
                        @foreach ($clients as $client)
                            <tr>
                                <td>{{ $client->name }}</td>
                                <td class="text-end">${{ number_format($client->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </x-adminlte.table-analytic>
                @endif
            </x-adminlte.card>
        </div>

        {{-- NUEVA SECCIÓN DE STOCK --}}
        <div class="col-lg-4">
            <x-adminlte.card title="Alertas de Stock" type="white" :showTools="true">
                @if ($stockReport->isEmpty())
                    <x-adminlte.empty-state text="Todo el stock está al día" />
                @else
                    <x-adminlte.table-analytic>
                        <x-slot name="thead">
                            <tr>
                                <th>Producto</th>
                                <th class="text-end">Stock</th>
                                <th class="text-center">Mín.</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </x-slot>
                        @foreach ($stockReport as $item)
                            <tr>
                                <td>
                                    <span class="text-sm text-muted text-uppercase d-block text-truncate"
                                        style="max-width: 180px;" title="{{ $item['name'] }}">
                                        {{ $item['name'] }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="font-weight-bold">{{ $item['stock'] }}</span>
                                </td>
                                <td class="text-center text-muted">
                                    <small>{{ $item['threshold'] }}</small>
                                </td>
                                <td class="text-center">
                                    @if ($item['is_low'])
                                        <span class="badge-custom badge-custom-crimson">Bajo</span>
                                    @elseif ($item['is_near'])
                                        <span class="badge-custom badge-custom-apricot">Próximo</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-adminlte.table-analytic>
                @endif
            </x-adminlte.card>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.analyticsData = @json($chartData);
    </script>
    @vite('resources/js/pages/analytics-dashboard.js')
@endpush
