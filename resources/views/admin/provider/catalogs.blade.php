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
        <div class="d-flex flex-wrap gap-2">
            @canResource('providers.delete')
                @can('clearCatalog', \App\Models\Provider::class)
                <form action="{{ route('web.provider-catalogs.clear') }}" method="POST" data-catalog-clear-form>
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="confirm_clear" value="0">
                    <button class="btn btn-outline-danger">Limpiar todos los catálogos web</button>
                </form>
                @endcan
            @endcanResource
            <a class="btn btn-outline-secondary" href="{{ route('web.providers.index') }}">Proveedores</a>
        </div>
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
                @if($sourceUrls !== [])
                    <label class="form-label mt-3" for="catalog-source">URL o categoría del proveedor</label>
                    <select id="catalog-source" name="source_url" class="form-select">
                        @foreach($sourceUrls as $sourceUrl)
                            <option value="{{ $sourceUrl }}" @selected($selectedSourceUrl === $sourceUrl)>{{ $sourceUrl }}</option>
                        @endforeach
                    </select>
                    <small class="text-body-secondary">La consulta y el procesamiento corresponden a la URL seleccionada; las otras categorías conservan sus resultados.</small>
                @endif
            </form>
            @if($selectedProvider)
                <p class="text-break">URL seleccionada: {{ $selectedSourceUrl ?? 'Sin configurar' }}</p>
                @can('update', $selectedProvider)
                    <div class="d-flex flex-wrap gap-2">
                        @can('manageCatalogUrls', \App\Models\Provider::class)
                            <a class="btn btn-outline-secondary" href="{{ route('web.providers.edit', $selectedProvider->id) }}">Editar URL</a>
                        @endcan
                        <form action="{{ route('web.providers.catalog.refresh', $selectedProvider->id) }}" method="POST" data-catalog-query-form>
                            @csrf
                            <input type="hidden" name="source_url" value="{{ $selectedSourceUrl }}">
                            <button class="btn btn-primary" @disabled(!$selectedSourceUrl || $catalog?->status === 'queued')>Consultar ahora</button>
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
                <p>No hay proveedores con URLs de catálogo configuradas. Agregá una URL desde Proveedores.</p>
            @endif
            <div class="mt-3 {{ $catalog?->status === 'queued' ? '' : 'd-none' }}" data-catalog-query-progress aria-busy="{{ $catalog?->status === 'queued' ? 'true' : 'false' }}">
                <p class="small mb-2" role="status" aria-live="polite" data-catalog-progress-label>
                    @if($catalog?->pagination)
                        Leyendo el catálogo… Encontramos {{ count($catalog->products ?? []) }}
                        @if(isset($catalog->pagination['total']))
                            de {{ $catalog->pagination['total'] }}
                        @endif
                        productos.
                    @else
                        Consultando el catálogo del proveedor… Los productos aparecerán a medida que los recibamos.
                    @endif
                </p>
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
                    <strong>{{ $catalog->status === 'queued' && $catalog->pagination ? 'Consulta en curso' : ($labels[$catalog->status] ?? $catalog->status) }}</strong>
                    <p class="mb-1">{{ $catalog->status === 'queued' && $catalog->pagination ? 'Seguimos buscando productos. Cuando termine la consulta, podrás guardarlos con el botón Procesar.' : $catalog->message }}</p>
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
            @if(count($sourceUrls) > 1)
                <div class="table-responsive mt-3">
                    <table class="table table-sm mb-0">
                        <caption class="caption-top">Consultas de las URLs de este proveedor</caption>
                        <thead><tr><th>URL o categoría</th><th>Estado</th><th>Productos</th></tr></thead>
                        <tbody>
                            @foreach($sourceUrls as $sourceUrl)
                                @php($sourceCatalog = $sourceCatalogs->get($sourceUrl))
                                <tr>
                                    <td class="text-break"><a href="{{ route('web.provider-catalogs.index', ['provider' => $selectedProvider->id, 'source_url' => $sourceUrl]) }}">{{ $sourceUrl }}</a></td>
                                    <td>{{ ['queued' => 'En cola', 'complete' => 'Listado obtenido', 'partial' => 'Listado parcial', 'failed' => 'Error de consulta', 'unsupported' => 'Integración pendiente'][$sourceCatalog?->status] ?? 'Sin consultar' }}</td>
                                    <td>{{ count($sourceCatalog?->products ?? []) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    <details class="card mb-4" id="catalog-published-products" data-catalog-section="products:{{ $selectedProvider?->id }}:{{ hash('sha256', $selectedSourceUrl ?? '') }}">
        <summary class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">Productos publicados <span class="badge text-bg-secondary">{{ $entries->total() }}</span></h5>
            <span class="text-primary small" data-catalog-toggle-label data-open-label="Ocultar productos" data-closed-label="Mostrar productos">Mostrar productos</span>
        </summary>
        <div class="card-body">
            <p class="text-body-secondary">Los campos ausentes se muestran como «No informado». Todos los productos requieren revisión de equivalencia.</p>
            @if($entries->total())
                <p class="small text-body-secondary">Mostrando {{ $entries->firstItem() }} a {{ $entries->lastItem() }} de {{ $entries->total() }} productos.</p>
            @endif
            @forelse($entries as $index => $product)
                @include('admin.provider.partials.catalog-product', ['product' => $product])
            @empty
                <p class="mb-0">No hay productos extraídos para mostrar.</p>
            @endforelse
            {{ $entries->links('pagination::bootstrap-5') }}
        </div>
    </details>
    <details class="card mb-4" id="catalog-history" data-catalog-section="history">
        <summary class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">Historial procesado <span class="badge text-bg-secondary">{{ $history->total() }}</span></h5>
            <span class="text-primary small" data-catalog-toggle-label data-open-label="Ocultar historial" data-closed-label="Mostrar historial">Mostrar historial</span>
        </summary>
        <div class="card-body">
            <p class="text-body-secondary">Cada consulta procesada conserva sus productos y precios. Los registros anteriores permanecen al volver a consultar o cambiar la URL.</p>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Proveedor / URL</th><th>Consultado</th><th>Procesado</th><th>Productos</th><th>Alcance</th><th>Registro</th></tr></thead>
                    <tbody>
                        @forelse($history as $snapshot)
                            <tr>
                                <td class="text-break">{{ $snapshot->provider_name }}<small class="d-block text-body-secondary">{{ $snapshot->source_url }}</small></td>
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
    </details>
    <div class="card" id="catalog-comparison">
        <div class="card-header"><h5 class="mb-0">Comparar productos equivalentes</h5></div>
        <div class="card-body">
            <p>Elegí entre 2 y 8 productos procesados de proveedores distintos. Los combos incluyen todo el historial, agrupado por proveedor, con imagen, fecha y URL consultada. Buscá por nombre, código, proveedor o URL de categoría; desplazate para cargar más resultados.</p>
            <p class="small text-body-secondary">{{ $processedProductCount }} productos guardados. Comparás precios de las fechas indicadas, que pueden ser diferentes; revisá modelo, variante, presentación y condiciones en origen.</p>
            <div class="alert alert-warning d-none" role="alert" data-catalog-options-error>No se pudieron cargar los productos procesados. Cerrá y volvé a abrir el combo para reintentar.</div>
            <form method="POST" action="{{ route('web.provider-catalogs.compare') }}">
                @csrf
                <div class="row g-3">
                    @for($slot = 0; $slot < 8; $slot++)
                        <div class="col-lg-6">
                            <label class="form-label catalog-provider-label" for="compare-provider-{{ $slot }}">Filtrar proveedor del producto {{ $slot + 1 }}</label>
                            <select class="form-select mb-2 catalog-comparison-provider" id="compare-provider-{{ $slot }}" data-product-select="compare-entry-{{ $slot }}">
                                <option value="">Todos los proveedores con productos guardados</option>
                                @foreach($comparisonProviders as $comparisonProvider)
                                    <option value="{{ $comparisonProvider->provider_id }}" @selected($selectedItems->firstWhere('id', old('entries.'.$slot))?->snapshot->provider_id === $comparisonProvider->provider_id)>{{ $comparisonProvider->provider_name }}</option>
                                @endforeach
                            </select>
                            <label class="form-label catalog-product-label" for="compare-entry-{{ $slot }}">Producto {{ $slot + 1 }}{{ $slot < 2 ? ' (obligatorio)' : ' (opcional)' }}</label>
                            <select class="form-select catalog-comparison-select" data-products-url="{{ route('web.provider-catalogs.products') }}" id="compare-entry-{{ $slot }}" name="entries[]" @required($slot < 2)>
                                <option value="">Seleccionar producto</option>
                                @foreach($selectedItems->where('id', old('entries.'.$slot)) as $item)
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
