@php
    $quantity = $item?->quantity ?? 1;
    $unitPrice = $item?->unit_price ?? ($salePrice ?? 0);
    $subtotal = $item?->subtotal ?? $quantity * $unitPrice;
    $allowEditPrice = $allowEditPrice ?? false;
@endphp

<tr data-id="{{ $product->id }}" data-code="{{ $product->code }}">
    <td>
        <span class="text-muted small">
            {{ $product->name }}
        </span>
        <input type="hidden" name="items[INDEX][product_id]" value="{{ $product->id }}">
    </td>

    <td>
        <input type="text" class="form-control" value="{{ $stock }}" readonly>
    </td>

    <td>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" class="form-control unit-price" name="items[INDEX][unit_price]"
                value="{{ $unitPrice }}" {{ $allowEditPrice ? 'readonly' : 'readonly' }}>

            @if ($allowEditPrice)
                <button type="button" class="btn btn-outline-warning btn-edit-price" data-status="off"
                    title="Habilitar edición de precio">
                    <i class="fas fa-lock"></i>
                </button>
            @endif
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
