@extends('layouts.app')
@section('page-title', 'Catálogos web de proveedores')

@section('content')
<div class="container-fluid" data-catalog-page data-pending="{{ $catalogs->contains('status', 'queued') ? 'true' : 'false' }}">
    <x-adminlte.alert-manager />
    @if($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <p class="mb-0 text-body-secondary">Última consulta bajo demanda. Sin historial ni sincronización automática.</p>
        <a class="btn btn-outline-secondary" href="{{ route('web.providers.index') }}">Proveedores</a>
    </div>
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Consultar catálogo</h5></div>
        <div class="card-body">
            <form method="GET" class="mb-3">
                <label for="catalog-provider" class="form-label">Proveedor</label>
                <div class="d-flex gap-2">
                    <select id="catalog-provider" name="provider" class="form-select">
                        @foreach($providers as $provider)
                            <option value="{{ $provider->id }}" @selected($selectedProvider?->id === $provider->id)>{{ $provider->business_name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-primary">Ver</button>
                </div>
            </form>
            @if($selectedProvider)
                <p class="text-break">URL: {{ $selectedProvider->catalog_url ?? 'Sin configurar' }}</p>
                @can('update', $selectedProvider)
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-outline-secondary" href="{{ route('web.providers.edit', $selectedProvider->id) }}">Editar URL</a>
                        <form action="{{ route('web.providers.catalog.refresh', $selectedProvider->id) }}" method="POST">
                            @csrf
                            <button class="btn btn-primary" @disabled(!$selectedProvider->catalog_url || $catalog?->status === 'queued')>Consultar ahora</button>
                        </form>
                    </div>
                @endcan
            @else
                <p>No hay proveedores registrados.</p>
            @endif
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
                <article class="border rounded p-3 mb-3">
                    <div class="d-flex gap-3">
                        @if($product['image'])
                            <a href="{{ $product['image'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary align-self-start">Ver imagen</a>
                        @endif
                        <div class="flex-grow-1">
                            <h6>{{ $product['name'] ?? 'Nombre no informado' }}</h6>
                            <p class="fw-bold mb-1">Precio: {{ $product['price'] ?? 'No informado' }} · {{ $product['currency'] ?? 'Moneda no informada' }}</p>
                            <p class="mb-1">{{ $product['description'] ?? 'Descripción no informada' }}</p>
                            <div class="row g-2 small mt-1">
                                @foreach(['brand' => 'Marca', 'model' => 'Modelo', 'code' => 'Código', 'presentation' => 'Presentación', 'availability' => 'Disponibilidad'] as $field => $label)
                                    <div class="col-md-4"><strong>{{ $label }}:</strong> {{ $product[$field] ?? 'No informado' }}</div>
                                @endforeach
                            </div>
                            <div class="d-flex gap-2 align-items-center mt-2">
                                <span class="badge text-bg-warning">Equivalencia: requiere revisión</span>
                                @if($product['url'])<a href="{{ $product['url'] }}" target="_blank" rel="noopener noreferrer">Ver origen</a>@endif
                            </div>
                            <details class="mt-2"><summary>Todos los datos publicados por la fuente</summary><pre class="text-wrap text-break small mb-0">{{ json_encode($product['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></details>
                        </div>
                    </div>
                </article>
            @empty
                <p class="mb-0">No hay productos extraídos para mostrar.</p>
            @endforelse
            {{ $entries->links('pagination::bootstrap-5') }}
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Comparar productos equivalentes</h5></div>
        <div class="card-body">
            <p>Elegí entre 2 y 8 productos de proveedores distintos. Revisá modelo, variante, cantidad, presentación y condiciones de precio en origen. No se asocian por nombre ni por código interno del proveedor.</p>
            <form method="POST" action="{{ route('web.provider-catalogs.compare') }}">
                @csrf
                <div class="row g-3">
                    @for($slot = 0; $slot < 8; $slot++)
                        <div class="col-lg-6">
                            <label class="form-label" for="compare-entry-{{ $slot }}">Producto {{ $slot + 1 }}{{ $slot < 2 ? ' (obligatorio)' : ' (opcional)' }}</label>
                            <select class="form-select catalog-comparison-select" id="compare-entry-{{ $slot }}" name="entries[]" @required($slot < 2)>
                                <option value="">Seleccionar producto</option>
                                @foreach($providers as $provider)
                                    @php($source = $catalogs->get($provider->id))
                                    @if($source && in_array($source->status, ['partial', 'complete']) && $source->source_url === $provider->catalog_url)
                                        <optgroup label="{{ $provider->business_name }} · {{ $source->completed_at?->format('d/m H:i') }}">
                                            @foreach($source->products ?? [] as $index => $product)
                                                <option value="{{ $source->id }}:{{ $source->token }}:{{ $index }}">{{ $product['name'] ?? 'Sin nombre' }} · {{ $product['code'] ?? 'Sin código' }} · {{ $product['price'] ?? 'Sin precio' }} {{ $product['currency'] ?? 'Sin moneda' }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    @endfor
                </div>
                <div class="form-check my-3">
                    <input class="form-check-input" type="checkbox" name="equivalence_reviewed" value="1" id="equivalence-reviewed" required>
                    <label class="form-check-label" for="equivalence-reviewed">Revisé los detalles en origen y confirmo que los productos elegidos son equivalentes en modelo, variante y presentación.</label>
                </div>
                <button class="btn btn-primary">Comparar precios consultados</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/modules/providers/catalogs.js')
@endpush
