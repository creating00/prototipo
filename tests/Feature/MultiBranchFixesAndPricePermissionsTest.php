<?php

use App\Enums\PaymentType;
use App\Enums\PriceType;
use App\Enums\ProductStatus;
use App\Enums\RoleLabel;
use App\Models\Branch;
use App\Models\Category;
use App\Models\ExpenseType;
use App\Models\Product;
use App\Models\Province;
use App\Models\User;
use App\Services\Product\ProductBranchService;
use App\Services\Product\ProductPresenterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RoleLabel::cases() as $roleEnum) {
        Role::findOrCreate($roleEnum->value);
    }
    Role::findOrCreate('admin');

    $this->province = Province::create([
        'api_id' => '14',
        'name' => 'Córdoba',
    ]);

    $this->branch1 = Branch::create([
        'name' => 'Sucursal Córdoba 1',
        'province_id' => $this->province->id,
        'address' => 'Av. Colón 1000',
    ]);

    $this->branch2 = Branch::create([
        'name' => 'Sucursal Córdoba 2',
        'province_id' => $this->province->id,
        'address' => 'Av. General Paz 500',
    ]);

    $this->category = Category::create([
        'name' => 'General Category',
    ]);

    $this->expenseType = ExpenseType::create([
        'name' => 'Servicios Públicos',
    ]);

    // Provincial Admin (sin branch_id directo en user)
    $this->provincialAdmin = User::factory()->create([
        'name' => 'Provincial Admin User',
        'email' => 'provincial_admin_test@test.com',
        'province_id' => $this->province->id,
        'branch_id' => null,
    ]);
    $this->provincialAdmin->assignRole(RoleLabel::PROVINCIAL_ADMIN->value);

    // Regular Admin
    $this->regularAdmin = User::factory()->create([
        'name' => 'Regular Admin User',
        'email' => 'regular_admin_test@test.com',
        'branch_id' => null,
    ]);
    $this->regularAdmin->assignRole('admin');
});

test('provincial admin without fixed branch_id can edit product without type error', function () {
    $product = Product::create([
        'code' => 'PROD-TEST-001',
        'name' => 'Producto Test Multi-Branch',
        'category_id' => $this->category->id,
    ]);

    $this->actingAs($this->provincialAdmin);
    $response = $this->get(route('web.products.edit', $product->id));

    $response->assertStatus(200);
    $response->assertSee('Producto Test Multi-Branch');
});

test('provincial admin without fixed branch_id can create expense successfully', function () {
    $this->actingAs($this->provincialAdmin);

    $response = $this->post(route('web.expenses.store'), [
        'branch_id' => $this->branch1->id,
        'expense_type_id' => $this->expenseType->id,
        'date' => now()->format('Y-m-d'),
        'payment_type' => PaymentType::Cash->value,
        'amount_amount' => 15000,
        'amount_currency' => 1,
        'observation' => 'Gasto de prueba para sucursal',
    ]);

    $response->assertRedirect(route('web.expenses.index'));

    $this->assertDatabaseHas('expenses', [
        'expense_type_id' => $this->expenseType->id,
        'branch_id' => $this->branch1->id,
        'amount' => 15000,
        'user_id' => $this->provincialAdmin->id,
    ]);
});

test('regular admin is restricted from updating product prices while provincial admin can', function () {
    $product = Product::create([
        'code' => 'PROD-TEST-002',
        'name' => 'Producto Precios Restriction',
        'category_id' => $this->category->id,
    ]);

    $updatePayload = [
        'code' => 'PROD-TEST-002',
        'name' => 'Producto Precios Restriction',
        'category_id' => $this->category->id,
        'branch_id' => $this->branch1->id,
        'stock' => 20,
        'status' => ProductStatus::Available->value,
        'purchase_price_amount' => 500,
        'purchase_price_currency' => 1,
        'sale_price_amount' => 1000,
        'sale_price_currency' => 1,
    ];

    // Regular admin tries to update product
    $this->actingAs($this->regularAdmin);
    $responseRegular = $this->put(route('web.products.update', $product->id), $updatePayload);
    $responseRegular->assertRedirect(route('web.products.index'));

    // Regular admin should NOT create/update price records
    $branchPrices = $product->fresh()->productBranches->firstWhere('branch_id', $this->branch1->id)?->prices;
    expect($branchPrices?->where('type', 1)->first()?->amount ?? 0)->toEqual(0);

    // Provincial admin updates prices
    $this->actingAs($this->provincialAdmin);
    $responseProvincial = $this->put(route('web.products.update', $product->id), $updatePayload);
    $responseProvincial->assertRedirect(route('web.products.index'));

    $branchPricesUpdated = $product->fresh()->productBranches->firstWhere('branch_id', $this->branch1->id)?->prices;
    expect((float)$branchPricesUpdated?->where('type', 2)->first()?->amount)->toEqual(1000.0);
});

test('consolidated mode displays the maximum price among branches', function () {
    $product = Product::create([
        'code' => 'PROD-CEL-001',
        'name' => 'Celular Test',
        'category_id' => $this->category->id,
    ]);

    $branchService = app(ProductBranchService::class);

    // Branch 1: Sale Price 1000
    $branchService->createBranchDataForProduct($product, [
        'branch_id' => $this->branch1->id,
        'stock' => 5,
        'status' => ProductStatus::Available->value,
        'purchase_price_amount' => 500,
        'purchase_price_currency' => 1,
        'sale_price_amount' => 1000,
        'sale_price_currency' => 1,
    ]);

    // Branch 2: Sale Price 2000
    $this->actingAs($this->provincialAdmin);
    $branchService->createBranchDataForProduct($product, [
        'branch_id' => $this->branch2->id,
        'stock' => 10,
        'status' => ProductStatus::Available->value,
        'purchase_price_amount' => 800,
        'purchase_price_currency' => 1,
        'sale_price_amount' => 2000,
        'sale_price_currency' => 1,
    ]);

    $productWithBranches = Product::with(['productBranches.branch', 'productBranches.prices', 'providers'])->where('id', $product->id)->get();
    $presenter = new ProductPresenterService();

    $formattedData = $presenter->formatForDataTable($productWithBranches, null);

    // Debe mostrar 2.000,00 (el precio más alto entre sucursales)
    expect($formattedData[0]['sale_price'])->toContain('2.000,00');
    expect((float)$formattedData[0]['sale_price_raw'])->toEqual(2000.0);
});

test('in consolidated mode editing a product preserves individual branch stock and status while updating prices', function () {
    $product = Product::create([
        'code' => 'PROD-INDEP-004',
        'name' => 'Producto Stock Independiente',
        'category_id' => $this->category->id,
    ]);

    $branchService = app(ProductBranchService::class);

    // Branch 1: Stock 10, Sale Price 1000
    $branchService->createBranchDataForProduct($product, [
        'branch_id' => $this->branch1->id,
        'stock' => 10,
        'status' => ProductStatus::Available->value,
        'purchase_price_amount' => 500,
        'purchase_price_currency' => 1,
        'sale_price_amount' => 1000,
        'sale_price_currency' => 1,
    ]);

    // Branch 2: Stock 25, Sale Price 1200
    $branchService->createBranchDataForProduct($product, [
        'branch_id' => $this->branch2->id,
        'stock' => 25,
        'status' => ProductStatus::Available->value,
        'purchase_price_amount' => 600,
        'purchase_price_currency' => 1,
        'sale_price_amount' => 1200,
        'sale_price_currency' => 1,
    ]);

    $this->actingAs($this->provincialAdmin);
    session(['active_branch_id' => 'all']);

    // Actualizamos el producto en modo consolidado enviando nuevos precios pero SIN stock ni status (deshabilitados)
    $this->put(route('web.products.update', $product->id), [
        'code' => 'PROD-INDEP-004',
        'name' => 'Producto Stock Independiente Editado',
        'category_id' => $this->category->id,
        'branch_id' => 'all',
        'purchase_price_amount' => 1500,
        'purchase_price_currency' => 1,
        'sale_price_amount' => 3000,
        'sale_price_currency' => 1,
    ]);

    $freshProduct = $product->fresh(['productBranches.prices']);

    $pb1 = $freshProduct->productBranches->firstWhere('branch_id', $this->branch1->id);
    $pb2 = $freshProduct->productBranches->firstWhere('branch_id', $this->branch2->id);

    // Verificar que el stock individual de cada sucursal NO FUE ALTERADO
    expect($pb1->stock)->toEqual(10);
    expect($pb2->stock)->toEqual(25);

    // Verificar que los precios sí se actualizaron en ambas sucursales
    expect((float)$pb1->prices->firstWhere('type', PriceType::SALE->value)->amount)->toEqual(3000.0);
    expect((float)$pb2->prices->firstWhere('type', PriceType::SALE->value)->amount)->toEqual(3000.0);
});

test('in consolidated mode ABM is allowed for Products but blocked for Users, Expenses, Clients, and Sales', function () {
    $this->actingAs($this->provincialAdmin);

    // Simular sesión activa en modo consolidado ("all")
    session(['active_branch_id' => 'all']);

    // 1. Productos: PERMITIDO
    $responseProduct = $this->get(route('web.products.create'));
    $responseProduct->assertStatus(200);

    // 2. Usuarios: DENEGADO (Redirecciona a index con error)
    $responseUser = $this->get(route('web.users.create'));
    $responseUser->assertRedirect(route('web.users.index'));
    $responseUser->assertSessionHasErrors();

    // 3. Gastos: DENEGADO (Redirecciona a index con error)
    $responseExpense = $this->get(route('web.expenses.create'));
    $responseExpense->assertRedirect(route('web.expenses.index'));
    $responseExpense->assertSessionHasErrors();

    // 4. Clientes: DENEGADO (Redirecciona a index con error)
    $responseClient = $this->get(route('web.clients.create'));
    $responseClient->assertRedirect(route('web.clients.index'));
    $responseClient->assertSessionHasErrors();

    // 5. Ventas: DENEGADO (Redirecciona a index con error)
    $responseSale = $this->get(route('web.sales.create-client'));
    $responseSale->assertRedirect(route('web.sales.index'));
    $responseSale->assertSessionHasErrors();

    // 6. Pedidos: DENEGADO (Redirecciona a index con error)
    $responseOrder = $this->get(route('web.orders.create-client'));
    $responseOrder->assertRedirect(route('web.orders.index'));
    $responseOrder->assertSessionHasErrors();

    // 7. Pedidos a Proveedor: DENEGADO (Redirecciona a index con error)
    $responseProviderOrder = $this->get(route('web.provider-orders.create'));
    $responseProviderOrder->assertRedirect(route('web.provider-orders.index'));
    $responseProviderOrder->assertSessionHasErrors();
});

test('provincial admin can view expenses index without redirect loop', function () {
    $this->actingAs($this->provincialAdmin);
    session(['active_branch_id' => 'all']);

    $response = $this->get(route('web.expenses.index'));
    $response->assertStatus(200);
});

test('editing product in consolidated mode creates missing branch rows with default stock 0 without undefined key stock error', function () {
    $product = Product::create([
        'code' => 'PROD-NO-STOCK-005',
        'name' => 'Producto Sin Registro En Sucursal 2',
        'category_id' => $this->category->id,
    ]);

    // Solo creamos registro para Branch 1
    $branchService = app(ProductBranchService::class);
    $branchService->createBranchDataForProduct($product, [
        'branch_id' => $this->branch1->id,
        'stock' => 12,
        'status' => ProductStatus::Available->value,
        'purchase_price_amount' => 500,
        'purchase_price_currency' => 1,
        'sale_price_amount' => 1000,
        'sale_price_currency' => 1,
    ]);

    $this->actingAs($this->provincialAdmin);
    session(['active_branch_id' => 'all']);

    // Actualizamos en consolidado SIN enviar stock ni status
    $response = $this->put(route('web.products.update', $product->id), [
        'code' => 'PROD-NO-STOCK-005',
        'name' => 'Producto Sin Registro Editado',
        'category_id' => $this->category->id,
        'branch_id' => 'all',
        'purchase_price_amount' => 700,
        'purchase_price_currency' => 1,
        'sale_price_amount' => 1500,
        'sale_price_currency' => 1,
    ]);

    $response->assertRedirect(route('web.products.index'));

    $freshProduct = $product->fresh(['productBranches.prices']);
    $pb2 = $freshProduct->productBranches->firstWhere('branch_id', $this->branch2->id);

    expect($pb2)->not->toBeNull();
    expect($pb2->stock)->toEqual(0);
});
