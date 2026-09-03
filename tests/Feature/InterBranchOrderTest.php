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
use App\Services\Product\ProductStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createTestBranch(string $name = 'Sucursal Test'): Branch
{
    $province = Province::firstOrCreate(
        ['name' => 'Buenos Aires'],
        ['api_id' => '06', 'name_long' => 'Provincia de Buenos Aires']
    );

    return Branch::create([
        'province_id' => $province->id,
        'name' => $name,
        'phone' => '123456789',
        'address' => 'Calle Falsa 123',
    ]);
}

function createTestProduct(string $name = 'Producto Test'): Product
{
    return Product::create([
        'code' => 'PROD-'.rand(1000, 9999),
        'name' => $name,
        'description' => 'Descripción test',
    ]);
}

function createOrderRoleUser(string $roleName, Branch $branch): User
{
    Role::findOrCreate($roleName);

    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->assignRole($roleName);

    return $user;
}

function attachPurchasePrice(Product $product, Branch $branch, float $amount): void
{
    $productBranch = ProductBranch::firstOrCreate(
        ['product_id' => $product->id, 'branch_id' => $branch->id],
        ['stock' => 0, 'status' => 1]
    );

    $productBranch->prices()->create([
        'type' => \App\Enums\PriceType::PURCHASE->value,
        'currency' => CurrencyType::ARS->value,
        'amount' => $amount,
    ]);
}

test('only provincial admin can create inter-branch orders', function () {
    $branch = createTestBranch('Sucursal Principal');
    $admin = createOrderRoleUser(RoleLabel::ADMIN->value, $branch);
    $provincialAdmin = createOrderRoleUser(RoleLabel::PROVINCIAL_ADMIN->value, $branch);

    expect(Gate::forUser($admin)->allows('createBranch', Order::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('create_branch', Order::class))->toBeFalse()
        ->and(Gate::forUser($provincialAdmin)->allows('createBranch', Order::class))->toBeTrue()
        ->and(Gate::forUser($provincialAdmin)->allows('create_branch', Order::class))->toBeTrue();
});

test('only provincial admin can modify order item cost', function () {
    $branch = createTestBranch('Sucursal Principal');
    $admin = createOrderRoleUser(RoleLabel::ADMIN->value, $branch);
    $provincialAdmin = createOrderRoleUser(RoleLabel::PROVINCIAL_ADMIN->value, $branch);

    $client = Client::create([
        'branch_id' => $branch->id,
        'full_name' => 'Cliente Test',
        'document' => '22333444',
        'phone' => '3511234567',
    ]);

    $product = createTestProduct('Modulo Test');
    attachPurchasePrice($product, $branch, 2500);

    $orderService = app(OrderService::class);

    $this->actingAs($admin);
    $adminOrder = $orderService->createOrder([
        'branch_id' => $branch->id,
        'client_id' => $client->id,
        'customer_type' => Client::class,
        'source' => \App\Enums\OrderSource::Manual->value,
        'status' => OrderStatus::Pending->value,
        'user_id' => $admin->id,
        'exchange_rate' => 1000,
        'items' => [[
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 9999,
            'currency' => CurrencyType::ARS->value,
        ]],
    ]);

    expect((float) $adminOrder->items->first()->unit_price)->toBe(2500.0);

    $this->actingAs($provincialAdmin);
    $provincialOrder = $orderService->createOrder([
        'branch_id' => $branch->id,
        'client_id' => $client->id,
        'customer_type' => Client::class,
        'source' => \App\Enums\OrderSource::Manual->value,
        'status' => OrderStatus::Pending->value,
        'user_id' => $provincialAdmin->id,
        'exchange_rate' => 1000,
        'items' => [[
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 9999,
            'currency' => CurrencyType::ARS->value,
        ]],
    ]);

    expect((float) $provincialOrder->items->first()->unit_price)->toBe(9999.0);
});

test('inter-branch order is created in pending state without deducting stock', function () {
    $branch1 = createTestBranch('Sucursal Origen');
    $branch2 = createTestBranch('Sucursal Destino');
    $user = User::factory()->create(['branch_id' => $branch1->id]);
    $product = createTestProduct();

    // Stock inicial 10 en branch1
    app(ProductStockService::class)->addStock($product, 10, $branch1->id);

    $orderService = app(OrderService::class);

    $orderData = [
        'branch_id' => $branch1->id,
        'customer_id' => $branch2->id,
        'customer_type' => Branch::class,
        'source' => 1,
        'status' => OrderStatus::Pending->value,
        'user_id' => $user->id,
        'exchange_rate' => 1000,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 5,
                'unit_price' => 100,
                'currency' => CurrencyType::ARS->value,
            ],
        ],
    ];

    $order = $orderService->createOrder($orderData);

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->is_stock_sent)->toBeFalse();

    // El stock no debió cambiar en la sucursal al crear la orden
    expect($product->getStock($branch1->id))->toBe(10);
});

test('send to stock increases receiving branch stock and locks order modification', function () {
    $branch1 = createTestBranch('Sucursal Origen');
    $branch2 = createTestBranch('Sucursal Destino');
    $user = User::factory()->create(['branch_id' => $branch1->id]);
    $product = createTestProduct();

    $orderService = app(OrderService::class);

    $order = $orderService->createOrder([
        'branch_id' => $branch1->id,
        'customer_id' => $branch2->id,
        'customer_type' => Branch::class,
        'source' => 1,
        'status' => OrderStatus::Pending->value,
        'user_id' => $user->id,
        'exchange_rate' => 1000,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 15,
                'unit_price' => 200,
                'currency' => CurrencyType::ARS->value,
            ],
        ],
    ]);

    expect($product->getStock($branch2->id))->toBe(0);

    // Ejecutar enviar al stock
    $updatedOrder = $orderService->sendToStock($order->id);

    expect($updatedOrder->is_stock_sent)->toBeTrue()
        ->and($updatedOrder->canBeEdited())->toBeFalse();

    // Verificar que el stock de la sucursal solicitante aumentó en 15
    expect($product->getStock($branch2->id))->toBe(15);

    // Intentar modificar el pedido bloqueado debe lanzar excepción
    expect(fn () => $orderService->updateOrder($order->id, [
        'branch_id' => $branch1->id,
        'customer_id' => $branch2->id,
        'customer_type' => Branch::class,
        'source' => 1,
        'status' => OrderStatus::Pending->value,
        'user_id' => $user->id,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 20,
                'unit_price' => 200,
                'currency' => CurrencyType::ARS->value,
            ],
        ],
    ]))->toThrow(Exception::class, 'No se puede modificar un pedido que ya ha sido enviado al stock.');
});

test('payment status can be updated even after order is sent to stock', function () {
    $branch1 = createTestBranch('Sucursal Origen');
    $branch2 = createTestBranch('Sucursal Destino');
    $user = User::factory()->create(['branch_id' => $branch1->id]);
    $product = createTestProduct();

    $orderService = app(OrderService::class);

    $order = $orderService->createOrder([
        'branch_id' => $branch1->id,
        'customer_id' => $branch2->id,
        'customer_type' => Branch::class,
        'source' => 1,
        'status' => OrderStatus::Pending->value,
        'user_id' => $user->id,
        'exchange_rate' => 1000,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 50,
                'currency' => CurrencyType::ARS->value,
            ],
        ],
    ]);

    $orderService->sendToStock($order->id);

    // Actualizar estado de pago a Pagado (1)
    $paidOrder = $orderService->updatePaymentStatus($order->id, 1);
    expect($paidOrder->payment_status)->toBe(1)
        ->and($paidOrder->payment_status_label)->toBe('Pagado');

    // Volver a cambiar a Pendiente (2)
    $pendingOrder = $orderService->updatePaymentStatus($order->id, 2);
    expect($pendingOrder->payment_status)->toBe(2)
        ->and($pendingOrder->payment_status_label)->toBe('Pendiente');
});

test('convert to sale automatically deducts product stock from supplying branch', function () {
    $branch1 = createTestBranch('Sucursal Proveedora');
    $branch2 = createTestBranch('Sucursal Solicitante');
    $user = User::factory()->create(['branch_id' => $branch1->id]);
    $product = createTestProduct('Módulo A15');

    // Stock inicial 20 en la sucursal proveedora
    app(ProductStockService::class)->addStock($product, 20, $branch1->id);
    expect($product->getStock($branch1->id))->toBe(20);

    $orderService = app(OrderService::class);

    $order = $orderService->createOrder([
        'branch_id' => $branch1->id,
        'customer_id' => $branch2->id,
        'customer_type' => Branch::class,
        'source' => 1,
        'status' => OrderStatus::Pending->value,
        'user_id' => $user->id,
        'exchange_rate' => 1000,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 5,
                'unit_price' => 100,
                'currency' => CurrencyType::ARS->value,
            ],
        ],
    ]);

    // Convertir la orden a venta
    $sale = $orderService->convertToSale($order->id, [
        'user_id' => $user->id,
        'total_amount' => 500,
        'payment_type_1' => 1,
        'amount_received_1' => 500,
    ]);

    expect($sale)->toBeInstanceOf(\App\Models\Sale::class);

    // Verificar que el estado de la orden cambió a Convertida a Venta (4)
    $freshOrder = $order->fresh();
    expect($freshOrder->status->value)->toBe(OrderStatus::ConvertedToSale->value);

    // Verificar que el stock en la sucursal proveedora fue descontado de 20 a 15
    $product->refresh();
    expect($product->getStock($branch1->id))->toBe(15);
});

test('inter-branch order queries isolate purchased orders by branch context', function () {
    $branch1 = createTestBranch('General Paz');
    $branch2 = createTestBranch('Cofico');

    $user1 = User::factory()->create(['branch_id' => $branch1->id]);
    $user2 = User::factory()->create(['branch_id' => $branch2->id]);

    $product = createTestProduct();

    $orderService = app(OrderService::class);

    // Pedido A: General Paz (Branch 1) solicita a Cofico (Branch 2)
    $orderA = $orderService->createOrder([
        'branch_id' => $branch2->id,
        'customer_id' => $branch1->id,
        'customer_type' => Branch::class,
        'source' => 1,
        'status' => OrderStatus::Pending->value,
        'user_id' => $user1->id,
        'exchange_rate' => 1000,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 100,
                'currency' => CurrencyType::ARS->value,
            ],
        ],
    ]);

    // Pedido B: Cofico (Branch 2) solicita a General Paz (Branch 1)
    $orderB = $orderService->createOrder([
        'branch_id' => $branch1->id,
        'customer_id' => $branch2->id,
        'customer_type' => Branch::class,
        'source' => 1,
        'status' => OrderStatus::Pending->value,
        'user_id' => $user2->id,
        'exchange_rate' => 1000,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 3,
                'unit_price' => 150,
                'currency' => CurrencyType::ARS->value,
            ],
        ],
    ]);

    // Simular contexto de General Paz (user1)
    $this->actingAs($user1);
    $purchases1 = $orderService->getPurchasedOrders();
    expect($purchases1->pluck('id')->toArray())->toContain($orderA->id)
        ->and($purchases1->pluck('id')->toArray())->not->toContain($orderB->id);

    // Simular contexto de Cofico (user2)
    $this->actingAs($user2);
    $purchases2 = $orderService->getPurchasedOrders();
    expect($purchases2->pluck('id')->toArray())->toContain($orderB->id)
        ->and($purchases2->pluck('id')->toArray())->not->toContain($orderA->id);
});

test('manual orders are strictly isolated to the creating branch and invisible to other branches', function () {
    $branch1 = createTestBranch('General Paz');
    $branch2 = createTestBranch('Cofico');

    $user1 = User::factory()->create(['branch_id' => $branch1->id]);
    $user2 = User::factory()->create(['branch_id' => $branch2->id]);

    $client = \App\Models\Client::create([
        'branch_id' => $branch1->id,
        'full_name' => 'Cliente Test',
        'document' => '12345678',
        'phone' => '12345678',
    ]);
    $product = createTestProduct();

    $orderService = app(OrderService::class);

    // Crear un Pedido Manual en General Paz (Branch 1)
    $manualOrder = $orderService->createOrder([
        'branch_id' => $branch1->id,
        'client_id' => $client->id,
        'customer_id' => $client->id,
        'customer_type' => \App\Models\Client::class,
        'source' => 1,
        'status' => OrderStatus::Pending->value,
        'user_id' => $user1->id,
        'exchange_rate' => 1000,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
                'currency' => CurrencyType::ARS->value,
            ],
        ],
    ]);

    // En General Paz (Branch 1): El pedido manual aparece en getAllOrders() y NO en getPurchasedOrders()
    $this->actingAs($user1);
    $ordersBranch1 = $orderService->getAllOrders($user1);
    expect($ordersBranch1->pluck('id')->toArray())->toContain($manualOrder->id);

    $purchasesBranch1 = $orderService->getPurchasedOrders();
    expect($purchasesBranch1->pluck('id')->toArray())->not->toContain($manualOrder->id);

    // En Cofico (Branch 2): El pedido manual NO debe aparecer NI en getAllOrders() NI en getPurchasedOrders()
    $this->actingAs($user2);
    $ordersBranch2 = $orderService->getAllOrders($user2);
    expect($ordersBranch2->pluck('id')->toArray())->not->toContain($manualOrder->id);

    $purchasesBranch2 = $orderService->getPurchasedOrders();
    expect($purchasesBranch2->pluck('id')->toArray())->not->toContain($manualOrder->id);
});

test('send to stock automatically updates product purchase price / cost in stock for manual order', function () {
    $branch = createTestBranch('Sucursal Central');
    $user = createOrderRoleUser(RoleLabel::PROVINCIAL_ADMIN->value, $branch);
    $client = \App\Models\Client::create([
        'branch_id' => $branch->id,
        'full_name' => 'Cliente Test',
        'document' => '99887766',
        'phone' => '12345678',
    ]);
    $product = createTestProduct('Modulo Pantalla OLED');

    $orderService = app(OrderService::class);

    $this->actingAs($user);

    $order = $orderService->createOrder([
        'branch_id' => $branch->id,
        'client_id' => $client->id,
        'customer_id' => $client->id,
        'customer_type' => \App\Models\Client::class,
        'source' => \App\Enums\OrderSource::Manual->value,
        'status' => OrderStatus::Pending->value,
        'user_id' => $user->id,
        'exchange_rate' => 1200,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 10,
                'unit_price' => 15500.50,
                'currency' => CurrencyType::ARS->value,
            ],
        ],
    ]);

    // Ejecutar enviar al stock
    $orderService->sendToStock($order->id);

    // Verificar que el stock aumentó en la sucursal
    expect($product->getStock($branch->id))->toBe(10);

    // Verificar que el precio de compra (costo) fue actualizado automáticamente en la sucursal
    $purchasePrice = $product->purchasePrice($branch->id);
    expect((float) $purchasePrice)->toBe(15500.50);
});

test('send to stock automatically updates product purchase price in receiving branch for branch order', function () {
    $supplyingBranch = createTestBranch('Sucursal Proveedora');
    $receivingBranch = createTestBranch('Sucursal Receptora');
    $user = createOrderRoleUser(RoleLabel::PROVINCIAL_ADMIN->value, $supplyingBranch);
    $product = createTestProduct('Batería iPhone 13');

    $orderService = app(OrderService::class);

    $this->actingAs($user);

    $order = $orderService->createOrder([
        'branch_id' => $supplyingBranch->id,
        'customer_id' => $receivingBranch->id,
        'customer_type' => Branch::class,
        'source' => 1,
        'status' => OrderStatus::Pending->value,
        'user_id' => $user->id,
        'exchange_rate' => 1200,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 5,
                'unit_price' => 8200.00,
                'currency' => CurrencyType::ARS->value,
            ],
        ],
    ]);

    // Ejecutar enviar al stock
    $orderService->sendToStock($order->id);

    // Verificar que el stock aumentó en la sucursal receptora
    expect($product->getStock($receivingBranch->id))->toBe(5);

    // Verificar que el precio de compra (costo) fue actualizado en la sucursal receptora
    $purchasePrice = $product->purchasePrice($receivingBranch->id);
    expect((float) $purchasePrice)->toBe(8200.00);
});
