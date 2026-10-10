@extends('layouts.app')
@section('page-title', 'Catálogos web de proveedores')

@section('content')
<div class="container-fluid" data-catalog-page data-pending="{{ $catalogs->contains('status', 'queued') ? 'true' : 'false' }}">
    <x-adminlte.alert-manager />
    @if($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <p class="mb-0 text-body-secondary">Consultá los productos publicados, revisá el resultado y procesalo para conservarlo en el historial.</p>
        <a class="btn btn-outline-secondary" href="{{ route('web.providers.index') }}">Proveedores</a>
    </div>
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Consultar catálogo</h5></div>
        <div class="card-body">
            <form method="GET" class="mb-3" data-catalog-provider-form>
                <label for="catalog-provider" class="form-label">Proveedor</label>
                <div class="d-flex gap-2">
                    <select id="catalog-provider" name="provider" class="form-select">
                        @foreach($providers as $provider)
                            <option value="{{ $provider->id }}" @selected($selectedProvider?->id === $provider->id)>{{ $provider->business_name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
            @if($selectedProvider)
                <p class="text-break">URL: {{ $selectedProvider->catalog_url ?? 'Sin configurar' }}</p>
                @can('update', $selectedProvider)
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-outline-secondary" href="{{ route('web.providers.edit', $selectedProvider->id) }}">Editar URL</a>
                        <form action="{{ route('web.providers.catalog.refresh', $selectedProvider->id) }}" method="POST" data-catalog-query-form>
                            @csrf
                            <button class="btn btn-primary" @disabled(!$selectedProvider->catalog_url || $catalog?->status === 'queued')>Consultar ahora</button>
                        </form>
                        @if($catalog && in_array($catalog->status, ['partial', 'complete']) && count($catalog->products ?? []) > 0)
                            <form action="{{ route('web.providers.catalog.process', $selectedProvider->id) }}" method="POST" data-catalog-process-form>
                                @csrf
                                <input type="hidden" name="token" value="{{ $catalog->token }}">
                                <button class="btn btn-success" @disabled($processed)>{{ $processed ? 'Consulta ya procesada' : 'Procesar' }}</button>
                            </form>
                        @endif
                    </div>
                @endcan
            @else
                <p>No hay proveedores registrados.</p>
            @endif
            <div class="mt-3 {{ $catalog?->status === 'queued' ? '' : 'd-none' }}" data-catalog-query-progress aria-busy="{{ $catalog?->status === 'queued' ? 'true' : 'false' }}">
                <p class="small mb-2" role="status" aria-live="polite" data-catalog-progress-label>Leyendo los productos del catálogo… Esperando la respuesta del proveedor.</p>
                <div class="progress catalog-progress" role="progressbar" aria-label="Consulta de productos en curso">
                    <div class="progress-bar catalog-progress-indeterminate"></div>
                </div>
            </div>
            <div class="mt-3 d-none" data-catalog-process-progress aria-busy="false">
                <p class="small mb-2" role="status" aria-live="polite">Procesando los productos… Guardando la consulta y sus productos en el historial.</p>
                <div class="progress catalog-progress" role="progressbar" aria-label="Procesamiento de productos en curso">
                    <div class="progress-bar bg-success catalog-progress-indeterminate"></div>
                </div>
            </div>
            @if($catalog)
                @php($labels = ['queued' => 'En cola', 'complete' => 'Listado obtenido', 'partial' => 'Listado parcial', 'failed' => 'Error de consulta', 'unsupported' => 'Integración pendiente'])
                <div class="alert {{ in_array($catalog->status, ['failed', 'unsupported']) ? 'alert-warning' : 'alert-info' }} mt-3 mb-0" role="status">
                    <strong>{{ $labels[$catalog->status] ?? $catalog->status }}</strong>
                    <p class="mb-1">{{ $catalog->message }}</p>
                    <small>Solicitado: {{ $catalog->requested_at->format('d/m/Y H:i:s') }}.
                        @if($catalog->completed_at) Finalizado: {{ $catalog->completed_at->format('d/m/Y H:i:s') }}. @endif
                        {{ count($catalog->products ?? []) }} productos. Los datos pueden cambiar en origen; consultá nuevamente antes de comprar.
                    </small>
                </div>
                <p class="small mt-2 mb-0">
                    @if($processed)
                        Guardada en el historial el {{ $processed->processed_at->format('d/m/Y H:i:s') }}.
                        <a href="{{ route('web.provider-catalogs.history', $processed) }}">Abrir registro procesado</a>
                    @elseif(in_array($catalog->status, ['partial', 'complete']))
                        Vista previa: presioná «Procesar» para guardar estos productos en el historial y habilitarlos en los combos.
                    @endif
                </p>
            @else
                <p class="mt-3 mb-0">Todavía no se consultó este catálogo.</p>
            @endif
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Productos publicados</h5></div>
        <div class="card-body">
            <p class="text-body-secondary">Los campos ausentes se muestran como «No informado». Todos los productos requieren revisión de equivalencia.</p>
            @forelse($entries as $index => $product)
                @include('admin.provider.partials.catalog-product', ['product' => $product])
            @empty
                <p class="mb-0">No hay productos extraídos para mostrar.</p>
            @endforelse
            {{ $entries->links('pagination::bootstrap-5') }}
        </div>
    </div>
    <div class="card mb-4" id="catalog-history">
        <div class="card-header"><h5 class="mb-0">Historial procesado</h5></div>
        <div class="card-body">
            <p class="text-body-secondary">Cada consulta procesada conserva sus productos y precios. Los registros anteriores permanecen al volver a consultar o cambiar la URL.</p>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Proveedor</th><th>Consultado</th><th>Procesado</th><th>Productos</th><th>Alcance</th><th>Registro</th></tr></thead>
                    <tbody>
                        @forelse($history as $snapshot)
                            <tr>
                                <td>{{ $snapshot->provider_name }}</td>
                                <td>{{ $snapshot->queried_at->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $snapshot->processed_at->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $snapshot->product_count }}</td>
                                <td><span class="badge {{ $snapshot->status === 'partial' ? 'text-bg-warning' : 'text-bg-success' }}">{{ $snapshot->status === 'partial' ? 'Listado parcial' : 'Listado obtenido' }}</span></td>
                                <td><a href="{{ route('web.provider-catalogs.history', $snapshot) }}">Abrir registro #{{ $snapshot->id }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6">Todavía no hay consultas procesadas. Consultá un proveedor y presioná «Procesar».</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $history->fragment('catalog-history')->links('pagination::bootstrap-5') }}
        </div>
    </div>
    <div class="card" id="catalog-comparison">
        <div class="card-header"><h5 class="mb-0">Comparar productos equivalentes</h5></div>
        <div class="card-body">
            <p>Elegí entre 2 y 8 productos procesados de proveedores distintos. Los combos incluyen todo el historial, agrupado por proveedor, con imagen y fecha de consulta. Buscá por nombre, código o proveedor; desplazate para cargar más resultados.</p>
            <p class="small text-body-secondary">{{ $processedProductCount }} productos guardados. Comparás precios de las fechas indicadas, que pueden ser diferentes; revisá modelo, variante, presentación y condiciones en origen.</p>
            <div class="alert alert-warning d-none" role="alert" data-catalog-options-error>No se pudieron cargar los productos procesados. Cerrá y volvé a abrir el combo para reintentar.</div>
            <form method="POST" action="{{ route('web.provider-catalogs.compare') }}">
                @csrf
                <div class="row g-3">
                    @for($slot = 0; $slot < 8; $slot++)
                        <div class="col-lg-6">
                            <label class="form-label" for="compare-entry-{{ $slot }}">Producto {{ $slot + 1 }}{{ $slot < 2 ? ' (obligatorio)' : ' (opcional)' }}</label>
                            <select class="form-select catalog-comparison-select" data-products-url="{{ route('web.provider-catalogs.products') }}" id="compare-entry-{{ $slot }}" name="entries[]" @required($slot < 2)>
                                <option value="">Seleccionar producto</option>
                                @foreach($selectedItems as $item)
                                    <option value="{{ $item->id }}" data-data="{{ json_encode($item->comparisonOption()) }}" @selected((string) old('entries.'.$slot) === (string) $item->id)>{{ $item->data['name'] ?? 'Nombre no informado' }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endfor
                </div>
                <div class="form-check my-3">
                    <input class="form-check-input" type="checkbox" name="equivalence_reviewed" value="1" id="equivalence-reviewed" required>
                    <label class="form-check-label" for="equivalence-reviewed">Revisé los detalles en origen y confirmo que los productos elegidos son equivalentes en modelo, variante y presentación.</label>
                </div>
                <button class="btn btn-primary" @disabled($processedProductCount === 0)>Comparar precios procesados</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/modules/providers/catalogs.js')
@endpush
