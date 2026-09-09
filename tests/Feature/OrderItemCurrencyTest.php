<?php

use App\Enums\CurrencyType;
use App\Enums\OrderStatus;
use App\Enums\RoleLabel;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\Province;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('order can be created with items in mixed currencies (ARS and USD)', function () {
    $province = Province::firstOrCreate(
        ['name' => 'Buenos Aires'],
        ['api_id' => '06', 'name_long' => 'Provincia de Buenos Aires']
    );

    $branch = Branch::create([
        'province_id' => $province->id,
        'name' => 'Sucursal Central',
        'phone' => '123456789',
        'address' => 'Av Siempre Viva 742',
    ]);

    Role::findOrCreate(RoleLabel::PROVINCIAL_ADMIN->value);
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->assignRole(RoleLabel::PROVINCIAL_ADMIN->value);
    $this->actingAs($user);

    $productArs = Product::create(['code' => 'P-ARS', 'name' => 'Producto ARS']);
    $productUsd = Product::create(['code' => 'P-USD', 'name' => 'Producto USD']);

    ProductBranch::create([
        'product_id' => $productArs->id,
        'branch_id' => $branch->id,
        'stock' => 10,
        'status' => 1,
    ]);

    ProductBranch::create([
        'product_id' => $productUsd->id,
        'branch_id' => $branch->id,
        'stock' => 10,
        'status' => 1,
    ]);

    $client = Client::create([
        'full_name' => 'Cliente Test',
        'document' => '12345678',
        'phone' => '555-1234',
        'branch_id' => $branch->id,
    ]);

    $orderService = app(OrderService::class);

    $orderData = [
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'customer_type' => Client::class,
        'client_id' => $client->id,
        'exchange_rate' => 1000.00,
        'status' => OrderStatus::Pending->value,
        'source' => 1,
        'items' => [
            [
                'product_id' => $productArs->id,
                'quantity' => 2,
                'unit_price' => 1500.00,
                'subtotal' => 3000.00,
                'currency' => CurrencyType::ARS->value,
            ],
            [
                'product_id' => $productUsd->id,
                'quantity' => 1,
                'unit_price' => 100.00,
                'subtotal' => 100.00,
                'currency' => CurrencyType::USD->value,
            ],
        ],
    ];

    $order = $orderService->createOrder($orderData);

    expect($order)->not->toBeNull();
    expect($order->items)->toHaveCount(2);

    $itemArs = $order->items->where('product_id', $productArs->id)->first();
    $itemUsd = $order->items->where('product_id', $productUsd->id)->first();

    expect($itemArs->currency)->toBe(CurrencyType::ARS);
    expect($itemArs->unit_price)->toEqual(1500.0);

    expect($itemUsd->currency)->toBe(CurrencyType::USD);
    expect($itemUsd->unit_price)->toEqual(100.0);
});
