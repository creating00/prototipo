<tr class="item-row">
    <td>
        <select name="items[{{ $index }}][provider_product_id]" class="form-control product-select" required>
            <option value="">Seleccione Producto</option>
            @if (isset($formData->products))
                @foreach ($formData->products as $pp)
                    <option value="{{ $pp['id'] }}" data-price="{{ $pp['price'] ?? 0 }}"
                        data-currency="{{ $pp['currency'] ?? '' }}" {{-- Usamos null-safe aquí también --}}
                        {{ isset($item) && $item?->provider_product_id == $pp['id'] ? 'selected' : '' }}>
                        {{ $pp['name'] }}
                    </option>
                @endforeach
            @endif
        </select>
    </td>
    <td>
        <input type="number" name="items[{{ $index }}][quantity]" class="form-control qty-input"
            value="{{ $item?->quantity ?? 1 }}" min="1" required>
    </td>
    <td class="align-middle">
        <div class="cost-input-container">
            {{-- EL CAMBIO CLAVE: $item?->currency?->value --}}
            <x-currency-price-input name="unit_cost_row_{{ $index }}" label="" :amount-value="$item?->unit_cost ?? 0"
                :currency-value="$item?->currency?->value" :currency-options="$formData->currencyOptions" />
        </div>
    </td>
    <td class="row-subtotal">
        {{-- Usamos el símbolo dinámico si existe el item, sino default $ --}}
        {{ $item?->currency?->symbol() ?? '$' }}
        {{ number_format(($item?->quantity ?? 0) * ($item?->unit_cost ?? 0), 2) }}
    </td>
    <td>
        <button type="button" class="btn btn-danger btn-sm remove-item">
            <i class="fas fa-times"></i>
        </button>
    </td>
</tr>
