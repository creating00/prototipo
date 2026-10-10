@extends('layouts.app')
@section('page-title', 'Catálogo procesado: '.$snapshot->provider_name)
@section('content')
<div class="container-fluid">
    <a class="btn btn-outline-secondary mb-3" href="{{ route('web.provider-catalogs.index', ['provider' => $snapshot->provider_id]) }}#catalog-history">Volver al historial y comparación</a>
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">{{ $snapshot->provider_name }} · Registro #{{ $snapshot->id }}</h5></div>
        <div class="card-body">
            <p class="mb-1"><strong>Consulta:</strong> {{ $snapshot->queried_at->format('d/m/Y H:i:s') }} · <strong>Procesamiento:</strong> {{ $snapshot->processed_at->format('d/m/Y H:i:s') }}</p>
            <p class="text-break mb-1"><strong>URL consultada:</strong> {{ $snapshot->source_url }}</p>
            <p class="mb-0">{{ $snapshot->product_count }} productos. Los precios corresponden a esta consulta histórica.</p>
            <div class="alert alert-info mt-3 mb-0"><strong>{{ $snapshot->status === 'partial' ? 'Listado parcial' : 'Listado obtenido' }}</strong><br>{{ $snapshot->message }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Productos guardados</h5></div>
        <div class="card-body">
            @foreach($entries as $item)
                @include('admin.provider.partials.catalog-product', ['product' => $item->data])
            @endforeach
            {{ $entries->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
@push('scripts')
    @vite('resources/js/modules/providers/catalogs.js')
@endpush
