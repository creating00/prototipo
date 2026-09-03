@php
    $quantity = $item?->quantity ?? 1;
    $unitPrice = $item?->unit_price ?? ($salePrice ?? 0);
    $subtotal = $item?->subtotal ?? $quantity * $unitPrice;
    $allowEditPrice = $allowEditPrice ?? true;
    $currentCurrency = $currency ?? ($item?->currency ?? \App\Enums\CurrencyType::ARS);
    $colorClass = "bg-{$currentCurrency->color()} text-white";

    $user = auth()->user();
    $canEditCost = $user?->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value) ?? false;
    $isOrderContext = ($context ?? 'order') === 'order';
    $allowEditPrice = $allowEditPrice && (!$isOrderContext || $canEditCost);
    $canViewCost = $canViewCost ?? ($user?->hasAnyRole([\App\Enums\RoleLabel::ADMIN->value, \App\Enums\RoleLabel::PROVINCIAL_ADMIN->value]) ?? false);
    $effectiveBranchId = $branchId ?? ($product?->branch_id ?? ($item?->order?->branch_id ?? ($user?->branch_id ?? null)));
    $costModel = $canViewCost && $product ? ($product->purchasePriceModel($effectiveBranchId) ?? $product->purchasePriceModel(null)) : null;
    $costDisplay = $costDisplay ?? ($costModel ? $costModel->getFormattedAmount() : ($product ? '$ ' . number_format($product->purchasePrice($effectiveBranchId), 2, ',', '.') : '$ 0,00'));
    $showCostCell = $showCostCell ?? ($canViewCost && ($context ?? 'sale') !== 'order');
@endphp

{{-- Usar nullsafe para evitar error en id y code --}}
<tr data-id="{{ $product?->id ?? $item?->product_id }}" data-code="{{ $product?->code ?? 'N/A' }}">
    <td>
        <span class="text-muted small d-block text-truncate" style="max-width: 200px;"
            title="{{ $product?->name ?? 'Producto eliminado' }}">
            {{ $product?->name ?? 'Producto eliminado' }}
        </span>
        <input type="hidden" name="items[INDEX][product_id]" value="{{ $product?->id ?? $item?->product_id }}">
        <input type="hidden" name="items[INDEX][currency]" value="{{ $currentCurrency->value }}">
    </td>

    <td>
        <input type="text" class="form-control text-center" value="{{ $stock }}" readonly>
    </td>

    {{-- Celda de Costo (Visible para administradores) --}}
    @if ($showCostCell)
        <td>
            <div class="input-group">
                <span class="input-group-text bg-secondary text-white font-monospace">
                    $
                </span>
                <input type="text" class="form-control bg-light text-muted fw-bold cost-display" value="{{ $costDisplay }}" readonly title="Costo unitario">
            </div>
        </td>
    @endif

    <td>
        <div class="input-group">
            <span class="input-group-text currency-symbol {{ $colorClass }}">
                {{ $currentCurrency->symbol() }}
            </span>

            <input type="number" class="form-control unit-price" name="items[INDEX][unit_price]"
                value="{{ number_format($unitPrice, 2, '.', '') }}" {{ $allowEditPrice ? '' : 'readonly' }} step="0.01" min="0">

            @if (!empty($showLockToggle))
                <button type="button" class="btn btn-outline-warning btn-edit-price" data-status="off"
                    title="Habilitar edición de precio">
                    <i class="fas fa-lock"></i>
                </button>
            @endif
        </div>
    </td>

    <td>
        <input type="number" name="items[INDEX][quantity]" class="form-control quantity" min="1"
            step="1" value="{{ $quantity }}">
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
