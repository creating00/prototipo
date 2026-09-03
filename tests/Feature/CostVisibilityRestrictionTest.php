<?php

use App\Enums\RoleLabel;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBranch;
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
        'branch_id' => $this->branch->id,
    ]);
    $this->provincialAdmin->assignRole(RoleLabel::PROVINCIAL_ADMIN->value);

    $this->admin = User::factory()->create([
        'branch_id' => $this->branch->id,
    ]);
    $this->admin->assignRole(RoleLabel::ADMIN->value);

    $this->seller = User::factory()->create([
        'branch_id' => $this->branch->id,
    ]);
    $this->seller->assignRole(RoleLabel::SELLER->value);

    $this->product = Product::create([
        'code' => 'MOD-A35',
        'name' => 'MODULO SAMSUNG A35',
    ]);

    $pb = ProductBranch::create([
        'product_id' => $this->product->id,
        'branch_id'  => $this->branch->id,
        'stock'      => 10,
        'status'     => 1,
    ]);

    $pb->prices()->create([
        'type'     => \App\Enums\PriceType::PURCHASE->value,
        'currency' => 1,
        'amount'   => 18000,
    ]);

    $pb->prices()->create([
        'type'     => \App\Enums\PriceType::SALE->value,
        'currency' => 1,
        'amount'   => 35000,
    ]);
});

test('api inventory list exposes cost to admin and provincial_admin but hides it from seller', function () {
    // 1. Provincial Admin
    $this->actingAs($this->provincialAdmin);
    $resProvincial = $this->getJson('/api/inventory/list?q=A35&branch_id=' . $this->branch->id);
    $resProvincial->assertStatus(200);
    $dataProvincial = $resProvincial->json();
    expect($dataProvincial[0]['show_cost'])->toBeTrue()
        ->and((float)$dataProvincial[0]['cost'])->toBe(18000.0)
        ->and($dataProvincial[0]['cost_display'])->toContain('18.000');

    // 2. Regular Admin
    $this->actingAs($this->admin);
    $resAdmin = $this->getJson('/api/inventory/list?q=A35&branch_id=' . $this->branch->id);
    $resAdmin->assertStatus(200);
    $dataAdmin = $resAdmin->json();
    expect($dataAdmin[0]['show_cost'])->toBeTrue()
        ->and((float)$dataAdmin[0]['cost'])->toBe(18000.0)
        ->and($dataAdmin[0]['cost_display'])->toContain('18.000');

    // 3. Seller (Strictly restricted)
    $this->actingAs($this->seller);
    $resSeller = $this->getJson('/api/inventory/list?q=A35&branch_id=' . $this->branch->id);
    $resSeller->assertStatus(200);
    $dataSeller = $resSeller->json();
    expect($dataSeller[0]['show_cost'])->toBeFalse()
        ->and($dataSeller[0]['cost'])->toBeNull()
        ->and($dataSeller[0]['cost_display'])->toBeNull();
});

test('api inventory by-code includes cost in html for admin and provincial_admin but omits it for seller', function () {
    // 1. Admin
    $this->actingAs($this->admin);
    $resAdmin = $this->getJson('/api/inventory/by-code?code=MOD-A35&branch_id=' . $this->branch->id . '&context=sale');
    $resAdmin->assertStatus(200);
    expect($resAdmin->json('product.show_cost'))->toBeTrue()
        ->and((float)$resAdmin->json('product.purchase_price'))->toBe(18000.0)
        ->and($resAdmin->json('html'))->toContain('18.000');

    // 2. Seller
    $this->actingAs($this->seller);
    $resSeller = $this->getJson('/api/inventory/by-code?code=MOD-A35&branch_id=' . $this->branch->id . '&context=sale');
    $resSeller->assertStatus(200);
    expect($resSeller->json('product.show_cost'))->toBeFalse()
        ->and($resSeller->json('product.purchase_price'))->toBeNull()
        ->and($resSeller->json('html'))->not->toContain('18.000');
});
