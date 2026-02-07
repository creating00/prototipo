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
use Illuminate\Support\Facades\Log;

class ProductWebController extends BaseProductController
{
    use AuthTrait;

    public function index()
    {
        $this->authorize('viewAny', Product::class);
        $rowData = $this->productService->getAllForDataTable();

        $headers = ['#', 'Código', 'Nombre', 'Precio Compra', 'Precio Venta', 'Stock', 'Proveedor/es', 'Estado'];
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
            $data = $request->except(['removeImage']);

            if ($request->hasFile('imageFile')) {
                $data['imageFile'] = $request->file('imageFile');
            }

            $product = $this->productService->create(
                data: $data,
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

        // Obtiene el producto (falle solo si el ID de producto no existe en absoluto)
        $product = $this->productService->getProductForEdit($id, $branchUserId);
        $this->authorize('update', $product);

        // Intentamos obtener la relación desde la colección cargada
        $productBranch = $product->productBranches->first();

        // Si no existe, instanciamos uno nuevo en memoria para el formulario
        if (!$productBranch) {
            $productBranch = new \App\Models\ProductBranch([
                'product_id' => $product->id,
                'branch_id' => $branchUserId,
                'stock' => 0,
                'status' => \App\Enums\ProductStatus::Available
            ]);
        }

        $branches = $isAdmin
            ? app(BranchService::class)->getAllBranches()
            : app(BranchService::class)->getAllBranches()->where('id', $branchUserId);

        $formData = new ProductFormData(
            product: $product,
            productBranch: $productBranch,
            statusOptions: ProductStatus::forSelect(),
            currencyOptions: CurrencyType::forSelect(),
            branches: $branches,
            categories: app(CategoryService::class)->getAllCategories(),
            provinces: \App\Models\Province::orderBy('name')->get(),
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
            // NO excluir imageFile e imageUrl, solo removeImage
            $data = $request->except(['removeImage']);

            // Asegurar que los archivos estén en el array si existen
            if ($request->hasFile('imageFile')) {
                $data['imageFile'] = $request->file('imageFile');
            }

            $this->productService->update(
                product: $product,
                data: $data, // Ahora incluye imageFile e imageUrl
                imageFile: $request->file('imageFile'),
                imageUrl: $request->input('imageUrl'),
                removeImage: $request->boolean('removeImage')
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
            $result = $this->productService->delete($product, $this->currentBranchId());

            return match ($result) {
                'global_delete' => redirect()->route('web.products.index')
                    ->with('success', 'Producto eliminado completamente del sistema.'),

                'branch_delete' => redirect()->route('web.products.index')
                    ->with('success', 'Los datos de stock y precio de su sucursal han sido eliminados.'),

                'not_found' => redirect()->back()
                    ->with('info', 'Su sucursal ya no tenía registros vinculados a este producto.'),

                default => redirect()->route('web.products.index')
                    ->with('warning', 'Acción finalizada con un estado desconocido.'),
            };
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
