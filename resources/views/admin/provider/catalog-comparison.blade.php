@extends('layouts.app')
@section('page-title', 'Comparación de catálogos')
@section('content')
<div class="container-fluid">
    <a class="btn btn-outline-secondary mb-3" href="{{ route('web.provider-catalogs.index') }}">Volver a catálogos</a>
    <div class="alert alert-info">Equivalencia confirmada manualmente para esta comparación. Precios guardados en el historial de las fechas indicadas; no se convierten monedas ni se equiparan impuestos, descuentos o condiciones de compra. Consultá y procesá nuevamente para guardar precios más recientes.</div>
    @foreach($groups as $currency => $rows)
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">{{ $currency }}</h5></div>
            <div class="card-body table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Proveedor / consulta</th><th>Producto</th><th>Detalles</th><th>Precio publicado</th><th>Disponibilidad / origen</th></tr></thead>
                    <tbody>
                    @foreach($rows as $row)
                        @php($product = $row['product'])
                        <tr>
                            <td>{{ $row['provider_name'] }}<br><small>{{ $row['queried_at']->format('d/m/Y H:i:s') }}</small><br><a class="small" href="{{ route('web.provider-catalogs.history', $row['snapshot_id']) }}">Registro #{{ $row['snapshot_id'] }}</a></td>
                            <td>
                                @php($imageUrl = \App\Services\Catalogs\PublicUrl::displayLink($product['image'] ?? null))
                                @if($imageUrl)<img src="{{ $imageUrl }}" alt="" width="48" height="48" class="rounded object-fit-contain me-2" loading="lazy" referrerpolicy="no-referrer">@endif
                                {{ $product['name'] ?? 'No informado' }}
                            </td>
                            <td>{{ $product['brand'] ?? 'Marca no informada' }}<br>{{ $product['model'] ?? 'Modelo no informado' }}<br>{{ $product['code'] ?? 'Código no informado' }}<br>{{ $product['presentation'] ?? 'Presentación no informada' }}</td>
                            <td class="fw-bold">{{ $product['price'] ?? 'No informado' }} {{ $product['currency'] ?? '' }}
                                @if(!$product['currency'])<small class="d-block text-body-secondary">Sin moneda: comparación numérica no disponible.</small>@endif
                            </td>
                            <td>{{ $product['availability'] ?? 'No informado' }}<br>@if(\App\Services\Catalogs\PublicUrl::displayLink($product['url'] ?? null))<a href="{{ $product['url'] }}" target="_blank" rel="noopener noreferrer">Ver producto de origen</a>@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
@endsection
