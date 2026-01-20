@php
    $quantity = $item?->quantity ?? 1;
    $unitPrice = $item?->unit_price ?? ($salePrice ?? 0);
    $subtotal = $item?->subtotal ?? $quantity * $unitPrice;
    $allowEditPrice = $allowEditPrice ?? false;
    $currentCurrency = $currency ?? ($item?->product?->currency ?? \App\Enums\CurrencyType::ARS);
    $colorClass = "bg-{$currentCurrency->color()} text-white";
@endphp

<tr data-id="{{ $product->id }}" data-code="{{ $product->code }}">
    <td>
        <span class="text-muted small d-block text-truncate" style="max-width: 200px;" title="{{ $product->name }}">
            {{ $product->name }}
        </span>
        <input type="hidden" name="items[INDEX][product_id]" value="{{ $product->id }}">
        <input type="hidden" name="items[INDEX][currency]" value="{{ $currentCurrency->code() }}">
    </td>

    <td>
        <input type="text" class="form-control" value="{{ $stock }}" readonly>
    </td>

    <td>
        <div class="input-group">
            <span class="input-group-text currency-symbol {{ $colorClass }}">
                {{ $currentCurrency->symbol() }}
            </span>

            <input type="number" class="form-control unit-price" name="items[INDEX][unit_price]"
                value="{{ number_format($unitPrice, 2, '.', '') }}" readonly step="0.01">

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
            max="{{ $stock }}" step="1" oninput="this.value = Math.min(this.value, this.max)"
            value="{{ $quantity }}">
    </td>

    <td>
        <div class="input-group">
            <span class="input-group-text {{ $colorClass }}">
                {{ $currentCurrency->symbol() }}
            </span>
            <input type="number" name="items[INDEX][subtotal]" class="form-control subtotal" step="0.01"
                value="{{ number_format($subtotal, 2, '.', '') }}" readonly>
        </div>
    </td>

    <td>
        <button type="button" class="btn btn-danger btn-sm btn-remove-item">
            <i class="fas fa-trash"></i>
        </button>
    </td>
</tr>
