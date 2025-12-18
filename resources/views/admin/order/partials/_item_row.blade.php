{{-- resources\views\admin\order\partials\_item_row.blade.php --}}
<tr data-id="{{ $product->id }}" data-code="{{ $product->code }}">
    <td>
        {{ $product->name }}
        <input type="hidden" name="items[INDEX][product_id]" value="{{ $product->id }}">
    </td>

    <td>
        <input type="number" class="form-control" value="{{ $product->stock }}" readonly>
    </td>

    <td>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" class="form-control unit-price" name="items[INDEX][unit_price]"
                value="{{ $product->sale_price }}" readonly>
        </div>
    </td>

    <td>
        <input type="number" name="items[INDEX][quantity]" class="form-control quantity" min="1" value="1">
    </td>

    <td>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="items[INDEX][subtotal]" class="form-control subtotal" step="0.01"
                value="{{ $product->sale_price }}" readonly>
        </div>
    </td>

    <td>
        <button type="button" class="btn btn-danger btn-sm btn-remove-item">
            <i class="fas fa-trash"></i>
        </button>
    </td>
</tr>
