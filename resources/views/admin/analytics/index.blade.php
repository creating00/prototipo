@extends('layouts.app')

@section('page-title', 'Panel de Control')

@push('styles')
    @vite('resources/css/modules/branches/branches-styles.css')
    <style>
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .loading-overlay-content {
            text-align: center;
            color: #fff;
        }
    </style>
@endpush

@section('content')
    {{-- OVERLAY DE CARGA --}}
    <div id="loading-overlay" class="loading-overlay" style="display: none;">
        <div class="loading-overlay-content">
            <div class="spinner-border text-light mb-3" role="status" style="width: 3rem; height: 3rem;">
                <span class="sr-only">Cargando...</span>
            </div>
            <h5 class="text-white font-weight-bold">Aplicando filtros...</h5>
            <p class="text-white-50 small mb-0">Por favor, espere un momento.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('web.analytics.index') }}" id="analytics-filters-form">
        {{-- BLOQUE DE FILTROS --}}
        <x-adminlte.card title="Filtros de Reporte" type="white">
            <x-slot:tools>
                <a href="{{ route('web.analytics.index') }}" class="btn btn-sm btn-outline-secondary"
                   onclick="document.getElementById('loading-overlay').style.display = 'flex';">
                    <i class="fas fa-sync-alt mr-1"></i> Restablecer
                </a>
            </x-slot:tools>

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
        </x-adminlte.card>

        {{-- ACTIVIDAD (Ventas Hoy, Mes, Año) --}}
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

        {{-- 3 NUEVAS TARJETAS DE ANALÍTICAS --}}
        <div class="row mb-4" x-data="{ 
            salesPaymentType: '{{ $currentFilters['sales_payment_type'] ?? '' }}',
            expensesPaymentType: '{{ $currentFilters['expenses_payment_type'] ?? '' }}',
            balancePaymentType: '{{ $currentFilters['balance_payment_type'] ?? '' }}'
        }">
            
            {{-- Tarjeta 1: Ventas del Mes --}}
            <div class="col-12 col-md-4 mb-3">
                <x-adminlte.card type="success" title="Ventas del Mes" icon="fas fa-money-bill-wave">
                    <div class="text-center py-2">
                        <h4 class="font-weight-bold mb-0 text-success">
                            $ {{ number_format($filteredSales['ars'], 2, ',', '.') }}
                        </h4>
                        <p class="text-muted mb-3" style="font-size: 0.9rem;">
                            U$D {{ number_format($filteredSales['usd'], 2, ',', '.') }}
                        </p>
                    </div>
                    <hr class="my-2">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="small text-muted mb-1">Forma de Pago</label>
                            <select name="sales_payment_type" class="form-control form-control-sm" 
                                x-model="salesPaymentType" @change="salesPaymentType = $event.target.value; $nextTick(() => $el.form.submit())">
                                <option value="">Todos los métodos</option>
                                <option value="1">Efectivo</option>
                                <option value="2">Tarjeta</option>
                                <option value="3">Transferencia</option>
                            </select>
                        </div>
                        
                        {{-- Cuenta destino (Solo transferencia) --}}
                        <div class="col-12 mt-2" x-show="salesPaymentType == '3'" x-cloak x-transition>
                            <label class="small text-muted mb-1">Cuenta Destino</label>
                            <select name="sales_bank_account_id" class="form-control form-control-sm" onchange="this.form.submit()">
                                <option value="">Todas las cuentas</option>
                                @foreach($bankAccounts as $id => $desc)
                                    <option value="{{ $id }}" {{ ($currentFilters['sales_bank_account_id'] == $id) ? 'selected' : '' }}>
                                        {{ $desc }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Banco (Solo tarjeta) --}}
                        <div class="col-12 mt-2" x-show="salesPaymentType == '2'" x-cloak x-transition>
                            <label class="small text-muted mb-1">Banco emisor</label>
                            <select name="sales_bank_id" class="form-control form-control-sm" onchange="this.form.submit()">
                                <option value="">Todos los bancos</option>
                                @foreach($banks as $id => $name)
                                    <option value="{{ $id }}" {{ ($currentFilters['sales_bank_id'] == $id) ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </x-adminlte.card>
            </div>

            {{-- Tarjeta 2: Gastos del Mes --}}
            <div class="col-12 col-md-4 mb-3">
                <x-adminlte.card type="danger" title="Gastos del Mes" icon="fas fa-file-invoice-dollar">
                    <div class="text-center py-2">
                        <h4 class="font-weight-bold mb-0 text-danger">
                            $ {{ number_format($filteredExpenses['ars'], 2, ',', '.') }}
                        </h4>
                        <p class="text-muted mb-3" style="font-size: 0.9rem;">
                            U$D {{ number_format($filteredExpenses['usd'], 2, ',', '.') }}
                        </p>
                    </div>
                    <hr class="my-2">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="small text-muted mb-1">Motivo del gasto</label>
                            <select name="expenses_expense_type_id" class="form-control form-control-sm" onchange="this.form.submit()">
                                <option value="">Todos los motivos</option>
                                @foreach($expenseTypes as $id => $name)
                                    <option value="{{ $id }}" {{ ($currentFilters['expenses_expense_type_id'] == $id) ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 mt-2">
                            <label class="small text-muted mb-1">Forma de Pago</label>
                            <select name="expenses_payment_type" class="form-control form-control-sm" 
                                x-model="expensesPaymentType" @change="expensesPaymentType = $event.target.value; $nextTick(() => $el.form.submit())">
                                <option value="">Todos los métodos</option>
                                <option value="1">Efectivo</option>
                                <option value="2">Tarjeta</option>
                                <option value="3">Transferencia</option>
                                <option value="4">Cheque</option>
                            </select>
                        </div>

                        {{-- Cuenta destino (Solo transferencia) --}}
                        <div class="col-12 mt-2" x-show="expensesPaymentType == '3'" x-cloak x-transition>
                            <label class="small text-muted mb-1">Cuenta Destino</label>
                            <select name="expenses_bank_account_id" class="form-control form-control-sm" onchange="this.form.submit()">
                                <option value="">Todas las cuentas</option>
                                @foreach($bankAccounts as $id => $desc)
                                    <option value="{{ $id }}" {{ ($currentFilters['expenses_bank_account_id'] == $id) ? 'selected' : '' }}>
                                        {{ $desc }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </x-adminlte.card>
            </div>

            {{-- Tarjeta 3: Balance del Mes --}}
            <div class="col-12 col-md-4 mb-3">
                <x-adminlte.card type="{{ $filteredBalance['ars'] >= 0 ? 'success' : 'danger' }}" title="Balance del Mes" icon="fas fa-balance-scale">
                    <div class="text-center py-2">
                        <h4 class="font-weight-bold mb-0 {{ $filteredBalance['ars'] >= 0 ? 'text-success' : 'text-danger' }}">
                            $ {{ number_format($filteredBalance['ars'], 2, ',', '.') }}
                        </h4>
                        <p class="text-muted mb-3" style="font-size: 0.9rem;">
                            U$D {{ number_format($filteredBalance['usd'], 2, ',', '.') }}
                        </p>
                    </div>
                    <hr class="my-2">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="small text-muted mb-1">Filtrar Balance por Pago</label>
                            <select name="balance_payment_type" class="form-control form-control-sm" 
                                x-model="balancePaymentType" @change="balancePaymentType = $event.target.value; $nextTick(() => $el.form.submit())">
                                <option value="">Todos los métodos</option>
                                <option value="1">Efectivo</option>
                                <option value="2">Tarjeta</option>
                                <option value="3">Transferencia</option>
                            </select>
                        </div>
                        
                        {{-- Cuenta destino (Solo transferencia) --}}
                        <div class="col-12 mt-2" x-show="balancePaymentType == '3'" x-cloak x-transition>
                            <label class="small text-muted mb-1">Cuenta Destino</label>
                            <select name="balance_bank_account_id" class="form-control form-control-sm" onchange="this.form.submit()">
                                <option value="">Todas las cuentas</option>
                                @foreach($bankAccounts as $id => $desc)
                                    <option value="{{ $id }}" {{ ($currentFilters['balance_bank_account_id'] == $id) ? 'selected' : '' }}>
                                        {{ $desc }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Banco (Solo tarjeta) --}}
                        <div class="col-12 mt-2" x-show="balancePaymentType == '2'" x-cloak x-transition>
                            <label class="small text-muted mb-1">Banco emisor</label>
                            <select name="balance_bank_id" class="form-control form-control-sm" onchange="this.form.submit()">
                                <option value="">Todos los bancos</option>
                                @foreach($banks as $id => $name)
                                    <option value="{{ $id }}" {{ ($currentFilters['balance_bank_id'] == $id) ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </x-adminlte.card>
            </div>
            
        </div>

        {{-- RESULTADOS --}}
        <h6 class="text-muted text-uppercase mb-2">Resultados</h6>
        <div class="row mb-5 justify-content-center">
            @foreach ($resultBoxes as $box)
                <div class="col-12 col-sm-6 col-md-3">
                    <x-adminlte.infobox icon="{{ $box['icon'] }}" color="{{ $box['color'] }}" text="{{ $box['text'] }}"
                        number="{{ $box['number'] ?? 0 }}" prefix="{{ $box['prefix'] ?? null }}" />
                </div>
            @endforeach
        </div>
    </form>

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
        
        const form = document.getElementById('analytics-filters-form');
        if (form) {
            const originalSubmit = form.submit;
            form.submit = function() {
                const overlay = document.getElementById('loading-overlay');
                if (overlay) overlay.style.display = 'flex';
                
                if (typeof originalSubmit === 'function') {
                    originalSubmit.call(form);
                } else {
                    HTMLFormElement.prototype.submit.call(form);
                }
            };
            
            form.addEventListener('submit', function() {
                const overlay = document.getElementById('loading-overlay');
                if (overlay) overlay.style.display = 'flex';
            });
        }
    </script>
    @vite('resources/js/pages/analytics-dashboard.js')
@endpush
