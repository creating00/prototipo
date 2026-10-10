@extends('layouts.app')
@section('page-title', 'Comparación de catálogos')
@section('content')
<div class="container-fluid">
    <a class="btn btn-outline-secondary mb-3" href="{{ route('web.provider-catalogs.index') }}">Volver a catálogos</a>
    <div class="alert alert-info">Equivalencia confirmada manualmente para esta comparación. Precios de las últimas consultas indicadas; no se convierten monedas ni se equiparan impuestos, descuentos o condiciones de compra.</div>
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
                            <td>{{ $row['provider']->business_name }}<br><small>{{ $row['queried_at']?->format('d/m/Y H:i:s') }}</small></td>
                            <td>{{ $product['name'] ?? 'No informado' }}</td>
                            <td>{{ $product['brand'] ?? 'Marca no informada' }}<br>{{ $product['model'] ?? 'Modelo no informado' }}<br>{{ $product['code'] ?? 'Código no informado' }}<br>{{ $product['presentation'] ?? 'Presentación no informada' }}</td>
                            <td class="fw-bold">{{ $product['price'] ?? 'No informado' }} {{ $product['currency'] ?? '' }}
                                @if(!$product['currency'])<small class="d-block text-body-secondary">Sin moneda: comparación numérica no disponible.</small>@endif
                            </td>
                            <td>{{ $product['availability'] ?? 'No informado' }}<br>@if($product['url'])<a href="{{ $product['url'] }}" target="_blank" rel="noopener noreferrer">Ver producto de origen</a>@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
@endsection
