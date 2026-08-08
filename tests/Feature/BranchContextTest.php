<?php

use App\Models\{Branch, User};
use App\Services\BranchService;
use App\Enums\RoleLabel;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Ensure roles exist in Spatie
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => RoleLabel::PROVINCIAL_ADMIN->value]);
    Role::firstOrCreate(['name' => 'seller']);
});

test('provincial admin can switch context to branches in their assigned province or all branches in province', function () {
    $provCordobaId = DB::table('provinces')->insertGetId([
        'api_id' => '14',
        'name' => 'Córdoba',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $provSantaFeId = DB::table('provinces')->insertGetId([
        'api_id' => '82',
        'name' => 'Santa Fe',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $branchCba1 = Branch::create(['name' => 'Córdoba Centro', 'address' => 'Av. Colón 100', 'province_id' => $provCordobaId]);
    $branchCba2 = Branch::create(['name' => 'Villa María', 'address' => 'San Martín 500', 'province_id' => $provCordobaId]);
    $branchStaFe = Branch::create(['name' => 'Rosario', 'address' => 'Peatonal Córdoba 1000', 'province_id' => $provSantaFeId]);

    $user = User::factory()->create([
        'province_id' => $provCordobaId,
        'branch_id' => $branchCba1->id,
    ]);
    $user->assignRole(RoleLabel::PROVINCIAL_ADMIN->value);

    $branchService = app(BranchService::class);
    $accessibleBranches = $branchService->getAccessibleBranchesForUser($user);

    expect($accessibleBranches->pluck('id')->toArray())
        ->toContain($branchCba1->id, $branchCba2->id)
        ->not->toContain($branchStaFe->id);

    // Switch context to specific valid branch
    $response = $this->actingAs($user)->post(route('web.branch-context.switch'), [
        'branch_id' => (string) $branchCba2->id,
    ]);

    $response->assertSessionHasNoErrors();
    expect(session('active_branch_id'))->toBe($branchCba2->id);

    // Switch context to consolidated 'all'
    $response = $this->actingAs($user)->post(route('web.branch-context.switch'), [
        'branch_id' => 'all',
    ]);

    $response->assertSessionHasNoErrors();
    expect(session('active_branch_id'))->toBe('all');

    // Attempt switch to branch outside assigned province -> Fails
    $response = $this->actingAs($user)->post(route('web.branch-context.switch'), [
        'branch_id' => (string) $branchStaFe->id,
    ]);

    expect(session('active_branch_id'))->not->toBe($branchStaFe->id);
});

test('product service datatable formatting handles consolidated branch mode without TypeError', function () {
    $provId = DB::table('provinces')->insertGetId([
        'api_id' => '15',
        'name' => 'Córdoba Test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $branch1 = Branch::create(['name' => 'Cba 1', 'address' => 'Addr 1', 'province_id' => $provId]);
    $branch2 = Branch::create(['name' => 'Cba 2', 'address' => 'Addr 2', 'province_id' => $provId]);

    $user = User::factory()->create([
        'province_id' => $provId,
        'branch_id' => $branch1->id,
    ]);
    $user->assignRole(RoleLabel::PROVINCIAL_ADMIN->value);

    $this->actingAs($user);
    session(['active_branch_id' => 'all']);

    $productService = app(\App\Services\ProductService::class);
    $data = $productService->getAllForDataTable();

    expect($data)->toBeArray();
});

test('existing user can be edited to become a provincial admin for a specific province', function () {
    $provId = DB::table('provinces')->insertGetId([
        'api_id' => '16',
        'name' => 'Córdoba Edit Test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $adminUser = User::factory()->create();
    $adminUser->assignRole('admin');

    $targetUser = User::factory()->create([
        'name' => 'Juan Perez',
        'email' => 'juan.perez@example.com',
    ]);

    $response = $this->actingAs($adminUser)->put(route('web.users.update', $targetUser->id), [
        'name' => 'Juan Perez Modificado',
        'email' => 'juan.perez@example.com',
        'role' => RoleLabel::PROVINCIAL_ADMIN->value,
        'province_id' => $provId,
        'branch_id' => null,
    ]);

    $response->assertRedirect(route('web.users.index'));
    $targetUser->refresh();

    expect($targetUser->hasRole(RoleLabel::PROVINCIAL_ADMIN->value))->toBeTrue();
    expect($targetUser->province_id)->toBe($provId);
    expect($targetUser->branch_id)->toBeNull();
});

test('client service datatable formatting handles consolidated branch mode without TypeError', function () {
    $provId = DB::table('provinces')->insertGetId([
        'api_id' => '17',
        'name' => 'Client Test Prov',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::factory()->create([
        'province_id' => $provId,
    ]);
    $user->assignRole(RoleLabel::PROVINCIAL_ADMIN->value);

    $this->actingAs($user);
    session(['active_branch_id' => 'all']);

    $clientService = app(\App\Services\ClientService::class);
    $data = $clientService->getAllClientsForDataTable(null, [$provId]);

    expect($data)->toBeArray();
});

test('sale service and scopeForBranch handle consolidated mode and array branchIds without TypeError', function () {
    $provId = DB::table('provinces')->insertGetId([
        'api_id' => '18',
        'name' => 'Sale Test Prov',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::factory()->create([
        'province_id' => $provId,
    ]);
    $user->assignRole(RoleLabel::PROVINCIAL_ADMIN->value);

    $this->actingAs($user);
    session(['active_branch_id' => 'all']);

    $saleService = app(\App\Services\SaleService::class);
    $data = $saleService->getAllSalesForDataTable();

    expect($data)->toBeArray();
});
