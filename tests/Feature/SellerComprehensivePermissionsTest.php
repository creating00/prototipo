<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\Product;
use App\Models\Province;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);

    $this->province = Province::create([
        'api_id' => '10',
        'name' => 'Cordoba',
    ]);

    $this->branch = Branch::create([
        'name' => 'Sucursal Central',
        'address' => 'Calle Falsa 123',
        'province_id' => $this->province->id,
    ]);

    $this->category = Category::create([
        'name' => 'General',
    ]);

    $this->client = Client::create([
        'full_name' => 'Juan Perez',
        'document' => '12345678',
        'phone' => '11223344',
        'province_id' => $this->province->id,
    ]);

    $this->expenseType = ExpenseType::create([
        'name' => 'Servicios',
        'is_system' => false,
    ]);

    $this->seller = User::factory()->create([
        'branch_id' => $this->branch->id,
    ]);
    $this->seller->assignRole('seller');
});

/* =========================================================================
   1. MÓDULO DE VENTAS (SALES)
   ========================================================================= */

test('seller can view sales index page', function () {
    $this->actingAs($this->seller);

    $response = $this->get(route('web.sales.index'));

    $response->assertStatus(200);
});

test('seller can view create client sale page', function () {
    $this->actingAs($this->seller);

    $response = $this->get(route('web.sales.create-client'));

    $response->assertStatus(200);
});

test('seller can view create branch sale page', function () {
    $this->actingAs($this->seller);

    $response = $this->get(route('web.sales.create-branch'));

    $response->assertStatus(200);
});

test('seller cannot view edit sale page', function () {
    $sale = Sale::create([
        'user_id' => $this->seller->id,
        'branch_id' => $this->branch->id,
        'internal_number' => 'V-00001',
        'customer_type' => 'App\Models\Client',
        'customer_id' => $this->client->id,
        'sale_type' => \App\Enums\SaleType::Sale,
        'status' => \App\Enums\SaleStatus::Paid,
        'sale_date' => now(),
        'subtotal' => 1000,
        'total' => 1000,
    ]);

    $this->actingAs($this->seller);

    $response = $this->get(route('web.sales.edit', $sale->id));

    $response->assertStatus(403);
});

test('seller cannot update a sale', function () {
    $sale = Sale::create([
        'user_id' => $this->seller->id,
        'branch_id' => $this->branch->id,
        'internal_number' => 'V-00001',
        'customer_type' => 'App\Models\Client',
        'customer_id' => $this->client->id,
        'sale_type' => \App\Enums\SaleType::Sale,
        'status' => \App\Enums\SaleStatus::Paid,
        'sale_date' => now(),
        'subtotal' => 1000,
        'total' => 1000,
    ]);

    $this->actingAs($this->seller);

    $response = $this->put(route('web.sales.update', $sale->id), [
        'internal_number' => 'V-00001',
        'customer_type' => 'App\Models\Client',
        'customer_id' => $this->client->id,
        'subtotal' => 9999,
        'total' => 9999,
    ]);

    $response->assertStatus(403);
});

test('seller cannot delete a sale', function () {
    $sale = Sale::create([
        'user_id' => $this->seller->id,
        'branch_id' => $this->branch->id,
        'internal_number' => 'V-00001',
        'customer_type' => 'App\Models\Client',
        'customer_id' => $this->client->id,
        'sale_type' => \App\Enums\SaleType::Sale,
        'status' => \App\Enums\SaleStatus::Paid,
        'sale_date' => now(),
        'subtotal' => 1000,
        'total' => 1000,
    ]);

    $this->actingAs($this->seller);

    $response = $this->delete(route('web.sales.destroy', $sale->id));

    $response->assertStatus(403);
});

/* =========================================================================
   2. MÓDULO DE GASTOS (EXPENSES)
   ========================================================================= */

test('seller can view expenses index page', function () {
    $this->actingAs($this->seller);

    $response = $this->get(route('web.expenses.index'));

    $response->assertStatus(200);
});

test('seller can view create expense page', function () {
    $this->actingAs($this->seller);

    $response = $this->get(route('web.expenses.create'));

    $response->assertStatus(200);
});

test('seller can store a new expense', function () {
    $this->actingAs($this->seller);

    $response = $this->post(route('web.expenses.store'), [
        'branch_id' => $this->branch->id,
        'expense_type_id' => $this->expenseType->id,
        'amount_amount' => 2500,
        'amount_currency' => 1,
        'payment_type' => 1,
        'date' => now()->format('Y-m-d'),
        'observation' => 'Gasto compra de insumos',
    ]);

    $response->assertRedirect(route('web.expenses.index'));
    $this->assertDatabaseHas('expenses', [
        'branch_id' => $this->branch->id,
        'expense_type_id' => $this->expenseType->id,
        'amount' => 2500,
        'observation' => 'Gasto compra de insumos',
    ]);
});

test('seller cannot view edit expense page', function () {
    $expense = Expense::create([
        'user_id' => $this->seller->id,
        'branch_id' => $this->branch->id,
        'expense_type_id' => $this->expenseType->id,
        'amount' => 500,
        'currency' => 1,
        'payment_type' => 1,
        'date' => now(),
        'observation' => 'Gasto de prueba',
    ]);

    $this->actingAs($this->seller);

    $response = $this->get(route('web.expenses.edit', $expense->id));

    $response->assertStatus(403);
});

test('seller cannot update an expense', function () {
    $expense = Expense::create([
        'user_id' => $this->seller->id,
        'branch_id' => $this->branch->id,
        'expense_type_id' => $this->expenseType->id,
        'amount' => 500,
        'currency' => 1,
        'payment_type' => 1,
        'date' => now(),
        'observation' => 'Gasto inicial',
    ]);

    $this->actingAs($this->seller);

    $response = $this->put(route('web.expenses.update', $expense->id), [
        'branch_id' => $this->branch->id,
        'expense_type_id' => $this->expenseType->id,
        'amount_amount' => 8888,
        'amount_currency' => 1,
        'payment_type' => 1,
        'date' => now()->format('Y-m-d'),
        'observation' => 'Modificacion prohibida',
    ]);

    $response->assertStatus(403);
});

test('seller cannot delete an expense', function () {
    $expense = Expense::create([
        'user_id' => $this->seller->id,
        'branch_id' => $this->branch->id,
        'expense_type_id' => $this->expenseType->id,
        'amount' => 500,
        'currency' => 1,
        'payment_type' => 1,
        'date' => now(),
        'observation' => 'Gasto a borrar',
    ]);

    $this->actingAs($this->seller);

    $response = $this->delete(route('web.expenses.destroy', $expense->id));

    $response->assertStatus(403);
});

/* =========================================================================
   3. MÓDULO DE PRODUCTOS (PRODUCTS)
   ========================================================================= */

test('seller can view products index page', function () {
    $this->actingAs($this->seller);

    $response = $this->get(route('web.products.index'));

    $response->assertStatus(200);
});

test('seller cannot view create product page', function () {
    $this->actingAs($this->seller);

    $response = $this->get(route('web.products.create'));

    $response->assertStatus(403);
});

test('seller cannot store a product', function () {
    $this->actingAs($this->seller);

    $response = $this->post(route('web.products.store'), [
        'name' => 'Producto Nuevo Prohibido',
        'code' => 'PROD-999',
        'category_id' => $this->category->id,
    ]);

    $response->assertStatus(403);
});

test('seller cannot view edit product page', function () {
    $product = Product::create([
        'name' => 'Producto Test',
        'code' => 'TEST-001',
        'category_id' => $this->category->id,
    ]);

    $this->actingAs($this->seller);

    $response = $this->get(route('web.products.edit', $product->id));

    $response->assertStatus(403);
});

test('seller cannot update a product', function () {
    $product = Product::create([
        'name' => 'Producto Test',
        'code' => 'TEST-001',
        'category_id' => $this->category->id,
    ]);

    $this->actingAs($this->seller);

    $response = $this->put(route('web.products.update', $product->id), [
        'name' => 'Producto Nombre Modificado',
        'code' => 'TEST-001',
        'category_id' => $this->category->id,
    ]);

    $response->assertStatus(403);
});

test('seller cannot delete a product', function () {
    $product = Product::create([
        'name' => 'Producto Borrar',
        'code' => 'TEST-DEL',
        'category_id' => $this->category->id,
    ]);

    $this->actingAs($this->seller);

    $response = $this->delete(route('web.products.destroy', $product->id));

    $response->assertStatus(403);
});
