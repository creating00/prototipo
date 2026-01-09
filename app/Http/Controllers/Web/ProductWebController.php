<?php

namespace App\Http\Controllers\Web;

use App\Enums\CurrencyType;
use App\Enums\ProductStatus;
use App\Http\Controllers\BaseProductController;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\Province;
use App\Services\BranchService;
use App\Services\CategoryService;
use App\Traits\AuthTrait;
use App\ViewModels\ProductFormData;
use Illuminate\Http\Request;

class ProductWebController extends BaseProductController
{
    use AuthTrait;

    public function index()
    {
        $this->authorize('viewAny', Product::class);
        $rowData = $this->productService->getAllForDataTable();

        $headers = ['#', 'Código', 'Nombre', 'Precio Compra', 'Precio Venta', 'Stock', 'Estado'];
        $hiddenFields = ['id', 'purchase_price_raw', 'sale_price_raw'];

        return view('admin.product.index', compact('rowData', 'headers', 'hiddenFields'));
    }

    public function create()
    {
        $this->authorize('create', Product::class);
        $branchUserId = $this->currentBranchId();

        /** @var \App\Models\User $user */
        $user = $this->currentUser();
        $isAdmin = $user->hasRole('admin');

        $branches = $isAdmin
            ? app(BranchService::class)->getAllBranches()
            : app(BranchService::class)->getAllBranches()->where('id', $branchUserId);

        $productBranch = new ProductBranch([
            'stock' => 0,
            'low_stock_threshold' => 5,
            'status' => ProductStatus::Available,
        ]);

        // Importante: colección vacía para evitar nulls
        $productBranch->setRelation('prices', collect());

        $formData = new ProductFormData(
            product: null,
            productBranch: $productBranch,
            statusOptions: ProductStatus::forSelect(),
            currencyOptions: CurrencyType::forSelect(),
            branches: $branches,
            categories: app(CategoryService::class)->getAllCategories(),
            provinces: Province::orderBy('name')->get(),
            branchUserId: $branchUserId,
            isAdmin: $isAdmin
        );

        return view('admin.product.create', compact('formData'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Product::class);
        try {
            $product = $this->productService->create(
                data: $request->except(['imageFile', 'imageUrl', 'removeImage']),
                imageFile: $request->file('imageFile'),
                imageUrl: $request->input('imageUrl')
            );

            return redirect()
                ->route('web.products.index')
                ->with('success', 'Producto creado exitosamente: ' . $product->name);
        } catch (\Illuminate\Validation\ValidationException $e) {

            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {

            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    public function edit(int $id)
    {
        /** @var \App\Models\User $user */
        $user = $this->currentUser();
        $isAdmin = $user->hasRole('admin');
        $branchUserId = $this->currentBranchId();

        // Obtenemos el producto. 
        // Si no es admin, el service probablemente ya filtra que pertenezca a la sucursal.
        $product = $this->productService->getProductForEdit($id, $branchUserId);

        $this->authorize('update', $product);

        // Obtenemos la relación de sucursal específica
        $productBranch = $product->productBranches->firstOrFail();

        // Lógica de sucursales para el ViewModel
        $branches = $isAdmin
            ? app(BranchService::class)->getAllBranches()
            : app(BranchService::class)->getAllBranches()->where('id', $productBranch->branch_id);

        $formData = new ProductFormData(
            product: $product,
            productBranch: $productBranch,
            statusOptions: ProductStatus::forSelect(),
            currencyOptions: CurrencyType::forSelect(),
            branches: $branches,
            categories: app(CategoryService::class)->getAllCategories(),
            provinces: Province::orderBy('name')->get(),
            branchUserId: $branchUserId,
            isAdmin: $isAdmin
        );

        return view('admin.product.edit', compact('formData'));
    }

    public function update(Request $request, int $id)
    {
        $product = $this->productService->getById($id);
        $this->authorize('update', $product);

        try {
            $this->productService->update(
                product: $product,
                data: $request->except(['imageFile', 'imageUrl', 'removeImage']),
                imageFile: $request->file('imageFile'),
                imageUrl: $request->input('imageUrl')
            );

            return redirect()
                ->route('web.products.index')
                ->with('success', 'Producto actualizado exitosamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(int $id)
    {
        $product = $this->productService->getById($id);
        $this->authorize('delete', $product);

        try {
            $this->productService->delete($product);

            return redirect()
                ->route('web.products.index')
                ->with('success', 'Producto eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
