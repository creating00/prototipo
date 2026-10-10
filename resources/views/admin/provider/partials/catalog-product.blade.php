@php
    $imageUrl = \App\Services\Catalogs\PublicUrl::displayLink($product['image'] ?? null);
    $productUrl = \App\Services\Catalogs\PublicUrl::displayLink($product['url'] ?? null);
@endphp
<article class="border rounded p-3 mb-3">
    <div class="d-flex gap-3">
        @if($imageUrl)
            <a href="{{ $imageUrl }}" target="_blank" rel="noopener noreferrer" class="align-self-start">
                <img src="{{ $imageUrl }}" alt="Imagen de {{ $product['name'] ?? 'producto' }}" class="catalog-product-image" width="72" height="72" loading="lazy" referrerpolicy="no-referrer">
            </a>
        @endif
        <div class="flex-grow-1 min-w-0">
            <h6>{{ $product['name'] ?? 'Nombre no informado' }}</h6>
            <p class="fw-bold mb-1">Precio: {{ $product['price'] ?? 'No informado' }} · {{ $product['currency'] ?? 'Moneda no informada' }}</p>
            <p class="mb-1">{{ $product['description'] ?? 'Descripción no informada' }}</p>
            <div class="row g-2 small mt-1">
                @foreach(['brand' => 'Marca', 'model' => 'Modelo', 'code' => 'Código', 'presentation' => 'Presentación', 'availability' => 'Disponibilidad'] as $field => $label)
                    <div class="col-md-4"><strong>{{ $label }}:</strong> {{ $product[$field] ?? 'No informado' }}</div>
                @endforeach
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                <span class="badge text-bg-warning">Equivalencia: requiere revisión</span>
                @if($productUrl)<a href="{{ $productUrl }}" target="_blank" rel="noopener noreferrer">Ver origen</a>@endif
            </div>
            <details class="mt-2"><summary>Todos los datos publicados por la fuente</summary><pre class="text-wrap text-break small mb-0">{{ json_encode($product['details'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></details>
        </div>
    </div>
</article>
