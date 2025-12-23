@props(['providerProduct', 'prices'])

<table class="table table-striped">
    <thead>
        <tr>
            <th>Precio</th>
            <th>Moneda</th>
            <th>Desde</th>
            <th>Hasta</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($prices as $price)
            <tr>
                <td>{{ $price->cost_price }}</td>
                <td>{{ $price->currency->label() }}</td>
                <td>{{ $price->effective_date->format('Y-m-d') }}</td>
                <td>{{ $price->end_date?->format('Y-m-d') ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- Formulario AJAX para agregar precio --}}
<div class="mt-3">
    <input type="hidden" name="provider_product_id" value="{{ $providerProduct->id }}">
    <div class="row g-3">
        <div class="col-md-3">
            <label>Precio</label>
            <input type="number" name="cost_price" class="form-control" step="0.01" required>
        </div>
        <div class="col-md-3">
            <label>Moneda</label>
            <select name="currency" class="form-control" required>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
            </select>
        </div>
        <div class="col-md-3">
            <label>Desde</label>
            <input type="date" name="effective_date" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label>Hasta</label>
            <input type="date" name="end_date" class="form-control">
        </div>
    </div>
</div>
