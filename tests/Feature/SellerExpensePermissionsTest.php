<?php

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\Province;
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

    $this->expenseType = ExpenseType::create([
        'name' => 'Servicios',
        'is_system' => false,
    ]);

    $this->seller = User::factory()->create([
        'branch_id' => $this->branch->id,
    ]);
    $this->seller->assignRole('seller');

    $this->admin = User::factory()->create([
        'branch_id' => $this->branch->id,
    ]);
    $this->admin->assignRole('admin');
});

test('seller can view expense index page', function () {
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
        'amount_amount' => 1500.50,
        'amount_currency' => 1,
        'payment_type' => 1,
        'date' => now()->format('Y-m-d'),
        'observation' => 'Gasto registrado por vendedor',
    ]);

    $response->assertRedirect(route('web.expenses.index'));
    $this->assertDatabaseHas('expenses', [
        'branch_id' => $this->branch->id,
        'expense_type_id' => $this->expenseType->id,
        'amount' => 1500.50,
        'observation' => 'Gasto registrado por vendedor',
    ]);
});

test('seller cannot view expense edit page', function () {
    $expense = Expense::create([
        'user_id' => $this->seller->id,
        'branch_id' => $this->branch->id,
        'expense_type_id' => $this->expenseType->id,
        'amount' => 500,
        'currency' => 1,
        'payment_type' => 1,
        'date' => now(),
        'observation' => 'Gasto existente',
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
        'observation' => 'Gasto original',
    ]);

    $this->actingAs($this->seller);

    $response = $this->put(route('web.expenses.update', $expense->id), [
        'branch_id' => $this->branch->id,
        'expense_type_id' => $this->expenseType->id,
        'amount_amount' => 9999,
        'amount_currency' => 1,
        'payment_type' => 1,
        'date' => now()->format('Y-m-d'),
        'observation' => 'Intento de edicion',
    ]);

    $response->assertStatus(403);
    $this->assertDatabaseHas('expenses', [
        'id' => $expense->id,
        'observation' => 'Gasto original',
    ]);
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
        'observation' => 'Gasto para borrar',
    ]);

    $this->actingAs($this->seller);

    $response = $this->delete(route('web.expenses.destroy', $expense->id));

    $response->assertStatus(403);
    $this->assertDatabaseHas('expenses', [
        'id' => $expense->id,
        'deleted_at' => null,
    ]);
});
