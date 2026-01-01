@php
    $quantity = $item?->quantity ?? 1;
    $unitPrice = $item?->unit_price ?? ($salePrice ?? 0);
    $subtotal = $item?->subtotal ?? $quantity * $unitPrice;
@endphp

<tr data-id="{{ $product->id }}" data-code="{{ $product->code }}">
    <td>
        {{ $product->name }}
        <input type="hidden" name="items[INDEX][product_id]" value="{{ $product->id }}">
    </td>

    <td>
        <input type="text" class="form-control" value="{{ $stock }}" readonly>
    </td>

    <td>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" class="form-control unit-price" name="items[INDEX][unit_price]"
                value="{{ $unitPrice }}" readonly>
        </div>
    </td>

    <td>
        <input type="number" name="items[INDEX][quantity]" class="form-control quantity" min="1"
            value="{{ $quantity }}">
    </td>

    <td>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="items[INDEX][subtotal]" class="form-control subtotal" step="0.01"
                value="{{ $subtotal }}" readonly>
        </div>
    </td>

    <td>
        <button type="button" class="btn btn-danger btn-sm btn-remove-item">
            <i class="fas fa-trash"></i>
        </button>
    </td>
</tr>
