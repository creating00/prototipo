{{-- resources/views/admin/product/partials/_modal-create.blade.php --}}
@php
    $branchUserId = auth()->user()?->branch_id;
    $user = auth()->user();
    $isAdmin = $user?->hasRole('admin');

    $branches = $isAdmin
        ? app(\App\Services\BranchService::class)->getAllBranches()
        : app(\App\Services\BranchService::class)->getAllBranches()->where('id', $branchUserId);

    $productBranch = new \App\Models\ProductBranch([
        'stock' => 0,
        'low_stock_threshold' => 5,
        'status' => \App\Enums\ProductStatus::Available,
    ]);
    $productBranch->setRelation('prices', collect());

    $quickFormData = new \App\ViewModels\ProductFormData(
        product: null,
        productBranch: $productBranch,
        statusOptions: \App\Enums\ProductStatus::forSelect(),
        currencyOptions: \App\Enums\CurrencyType::forSelect(),
        branches: $branches,
        categories: app(\App\Services\CategoryService::class)->getAllCategories(),
        provinces: \App\Models\Province::orderBy('name')->get(),
        branchUserId: $branchUserId,
        isAdmin: $isAdmin
    );
@endphp

<div class="modal fade" id="modalQuickProduct" tabindex="-1" aria-labelledby="modalQuickProductLabel" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title fs-6 fw-bold" id="modalQuickProductLabel">
                    <i class="fas fa-box-open me-2"></i> Registrar Producto Nuevo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formQuickProduct" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="fas fa-info-circle me-1"></i> El producto se registrará en el sistema con todos los datos ingresados y se agregará inmediatamente a tu pedido actual sin perder información ni recargar la página.
                    </div>

                    @include('admin.product.partials._form', [
                        'formData' => $quickFormData,
                    ])
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="fas fa-arrow-left me-1"></i> Cancelar y Volver al Pedido
                    </button>
                    <button type="submit" class="btn btn-success btn-sm" id="btnSaveQuickProduct">
                        <i class="fas fa-check-circle me-1"></i> Guardar y Agregar al Pedido
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
