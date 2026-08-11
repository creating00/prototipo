<?php

use App\Enums\RoleLabel;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\ProductBranchPrice;
use App\Models\RepairAmount;
use App\Models\User;
use App\Services\Product\ProductBranchPriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RoleLabel::cases() as $roleEnum) {
        Role::findOrCreate($roleEnum->value);
    }
    Role::findOrCreate('admin');

    $this->provinceId = DB::table('provinces')->insertGetId([
        'api_id' => '10',
        'name' => 'Cordoba',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->branch = Branch::create([
        'name' => 'Cordoba Centro',
        'code' => 'CC',
        'address' => 'Av Colon 100',
        'province_id' => $this->provinceId,
    ]);

    $this->provincialAdmin = User::factory()->create([
        'province_id' => $this->provinceId,
    ]);
    $this->provincialAdmin->assignRole(RoleLabel::PROVINCIAL_ADMIN->value);

    $this->regularAdmin = User::factory()->create();
    $this->regularAdmin->assignRole('admin');
});

test('only provincial_admin can update product prices in ProductBranchPriceService', function () {
    $product = Product::create([
        'code' => 'P-100',
        'name' => 'Laptop HP',
    ]);

    $pb = ProductBranch::create([
        'product_id' => $product->id,
        'branch_id' => $this->branch->id,
        'stock' => 10,
        'status' => 1,
    ]);

    $priceService = app(ProductBranchPriceService::class);

    // Initial price creation by provincial admin
    $this->actingAs($this->provincialAdmin);
    $priceService->createPricesForBranch($pb, [
        'purchase_price_amount' => 500,
        'purchase_price_currency' => 1,
        'sale_price_amount' => 800,
        'sale_price_currency' => 1,
    ]);

    $purchasePrice = $pb->prices()->where('type', 1)->first();
    expect((float)$purchasePrice->amount)->toBe(500.0);

    // Regular admin attempts to update price
    $this->actingAs($this->regularAdmin);
    $priceService->updatePricesForBranch($pb, [
        'purchase_price_amount' => 9999,
        'purchase_price_currency' => 1,
        'sale_price_amount' => 9999,
        'sale_price_currency' => 1,
    ]);

    $purchasePrice->refresh();
    expect((float)$purchasePrice->amount)->toBe(500.0); // Price remains 500!

    // Provincial admin updates price
    $this->actingAs($this->provincialAdmin);
    $priceService->updatePricesForBranch($pb, [
        'purchase_price_amount' => 600,
        'purchase_price_currency' => 1,
        'sale_price_amount' => 900,
        'sale_price_currency' => 1,
    ]);

    $purchasePrice->refresh();
    expect((float)$purchasePrice->amount)->toBe(600.0); // Price updated to 600!
});

test('only provincial_admin can create or delete repair amounts', function () {
    // Regular admin attempt
    $this->actingAs($this->regularAdmin);
    $response = $this->post(route('web.repair-amounts.store'), [
        'branch_id' => $this->branch->id,
        'repair_type' => 1,
        'amount' => 1500,
    ]);
    $response->assertStatus(403);
    expect(RepairAmount::count())->toBe(0);

    // Provincial admin attempt
    $this->actingAs($this->provincialAdmin);
    $response = $this->post(route('web.repair-amounts.store'), [
        'branch_id' => $this->branch->id,
        'repair_type' => 1,
        'amount' => 1500,
    ]);
    $response->assertRedirect(route('web.repair-amounts.index'));
    expect(RepairAmount::count())->toBe(1);
});
