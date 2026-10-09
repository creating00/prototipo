<?php

use App\Enums\CurrencyType;
use App\Enums\OrderStatus;
use App\Enums\RoleLabel;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\Province;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use App\Services\OrderService;
use App\Services\Product\ProductStockService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Notification::fake();
    foreach (['view', 'create_branch', 'update', 'approve', 'delete', 'cancel'] as $action) {
        Permission::findOrCreate("orders.$action");
    }
    $role = Role::findOrCreate(RoleLabel::PROVINCIAL_ADMIN->value);
    $role->givePermissionTo(Permission::where('name', 'like', 'orders.%')->get());
    $this->province = Province::create(['api_id' => '14', 'name' => 'Córdoba']);
    $this->targetProvince = Province::create(['api_id' => '66', 'name' => 'Salta']);
    $this->origin = Branch::create(['name' => 'Origen Córdoba', 'province_id' => $this->province->id]);
    $this->destinationA = Branch::create(['name' => 'Destino Salta A', 'province_id' => $this->targetProvince->id]);
    $this->destinationB = Branch::create(['name' => 'Destino Salta B', 'province_id' => $this->targetProvince->id]);
    $this->user = User::factory()->create(['branch_id' => $this->origin->id, 'province_id' => $this->province->id]);
    $this->user->assignRole($role);
    $this->actingAs($this->user);
    $this->product = Product::create(['code' => 'TRANSFER-001', 'name' => 'Pantalla']);
    app(ProductStockService::class)->addStock($this->product, 10, $this->origin->id);
    $this->service = app(OrderService::class);
    $this->payload = [
        'is_multi_destination' => 1,
        'branch_id' => $this->origin->id,
        'customer_type' => Branch::class,
        'source' => 1,
        'user_id' => $this->user->id,
        'status' => OrderStatus::Pending->value,
        'exchange_rate' => 1200,
        'items' => [
            ['product_id' => $this->product->id, 'destination_branch_id' => $this->destinationA->id,
                'destination_province_id' => $this->targetProvince->id, 'quantity' => 3,
                'unit_price' => 200, 'currency' => CurrencyType::ARS->value],
            ['product_id' => $this->product->id, 'destination_branch_id' => $this->destinationB->id,
                'destination_province_id' => $this->targetProvince->id, 'quantity' => 2,
                'unit_price' => 250, 'currency' => CurrencyType::ARS->value],
        ],
    ];
});

test('one order retains the same product for different destinations without moving stock', function () {
    $order = $this->service->createOrder($this->payload);
    expect(Order::count())->toBe(1)
        ->and($order->is_multi_destination)->toBeTrue()
        ->and($order->customer_id)->toBeNull()
        ->and($order->items)->toHaveCount(2)
        ->and((float) $order->totals[1])->toBe(1100.0)
        ->and($this->product->fresh()->getStock($this->origin->id))->toBe(10)
        ->and($this->product->fresh()->getStock($this->destinationA->id))->toBe(0);
});

test('transfer form and edit render a fixed origin and province-filtered destinations', function () {
    $this->withoutVite();
    Province::create(['api_id' => '99', 'name' => 'Provincia sin sucursales']);
    $this->get(route('web.orders.create-branch'))->assertOk()
        ->assertSee('Sucursal de origen')->assertSee('Provincia de destino')
        ->assertSee('Salta')->assertDontSee('Provincia sin sucursales');
    $order = $this->service->createOrder($this->payload);
    $this->get(route('web.orders.edit', $order))->assertOk()->assertSee('item-destination', false);
    $this->get('/api/inventory/by-code?'.http_build_query([
        'code' => $this->product->code, 'branch_id' => $this->origin->id,
        'is_multi_destination' => 1, 'context' => 'order', 'customer_type' => Branch::class,
    ]))->assertOk()->assertJsonPath('product.stock', 10);
});

test('invalid transfer destinations and quantities are rejected', function (string $case) {
    $payload = $this->payload;
    match ($case) {
        'province' => $payload['items'][0]['destination_province_id'] = $this->province->id,
        'origin' => $payload['items'][0]['destination_branch_id'] = $this->origin->id,
        'duplicate' => $payload['items'][1] = $payload['items'][0],
        'fractional' => $payload['items'][0]['quantity'] = 1.5,
        'missing' => $payload['items'][0]['destination_branch_id'] = null,
        'source' => $payload['branch_id'] = $this->destinationA->id,
        'deleted' => $this->destinationA->delete(),
    };
    expect(fn () => $this->service->createOrder($payload))->toThrow(ValidationException::class);
    expect(Order::count())->toBe(0);
})->with(['province', 'origin', 'duplicate', 'fractional', 'missing', 'source', 'deleted']);

test('editing transfer lines changes destinations and removes absent lines without moving stock', function () {
    $order = $this->service->createOrder($this->payload);
    $payload = $this->payload;
    $payload['items'] = [$payload['items'][1]];
    $payload['items'][0]['quantity'] = 4;
    $updated = $this->service->updateOrder($order->id, $payload);
    expect($updated->items)->toHaveCount(1)
        ->and($updated->items->first()->destination_branch_id)->toBe($this->destinationB->id)
        ->and((float) $updated->totals[1])->toBe(1000.0)
        ->and($this->product->fresh()->getStock($this->origin->id))->toBe(10);
});

test('sending to stock deducts combined origin quantity and credits each destination exactly once', function () {
    $order = $this->service->createOrder($this->payload);
    $this->postJson(route('web.orders.send-to-stock', $order))->assertOk()->assertJsonPath('success', true);
    expect($this->product->fresh()->getStock($this->origin->id))->toBe(5)
        ->and($this->product->fresh()->getStock($this->destinationA->id))->toBe(3)
        ->and($this->product->fresh()->getStock($this->destinationB->id))->toBe(2)
        ->and($order->fresh()->items->every(fn ($item) => $item->stock_sent_at !== null))->toBeTrue()
        ->and($order->fresh()->canBeEdited())->toBeFalse();
    $this->postJson(route('web.orders.send-to-stock', $order))->assertStatus(400);
    expect($this->product->fresh()->getStock($this->origin->id))->toBe(5)
        ->and($this->product->fresh()->getStock($this->destinationA->id))->toBe(3);
    expect(fn () => $this->service->updateOrder($order->id, $this->payload))->toThrow(Exception::class);
    expect(fn () => $this->service->deleteOrder($order->id))->toThrow(ValidationException::class);
    expect(fn () => $this->service->convertToSale($order->id))->toThrow(ValidationException::class);
    expect($this->product->fresh()->getStock($this->origin->id))->toBe(5);
});

test('combined demand exceeding source stock fails even when individual lines fit', function () {
    $payload = $this->payload;
    $payload['items'][0]['quantity'] = 6;
    $payload['items'][1]['quantity'] = 6;
    $order = $this->service->createOrder($payload);
    expect(fn () => $this->service->sendToStock($order->id))->toThrow(ValidationException::class);
    expect($this->product->fresh()->getStock($this->origin->id))->toBe(10)
        ->and($this->product->fresh()->getStock($this->destinationA->id))->toBe(0)
        ->and($order->fresh()->is_stock_sent)->toBeFalse();
});

test('a later stock failure rolls back all earlier source deductions and destination credits', function () {
    $secondProduct = Product::create(['code' => 'TRANSFER-002', 'name' => 'Batería']);
    app(ProductStockService::class)->addStock($secondProduct, 1, $this->origin->id);
    $payload = $this->payload;
    $line = $payload['items'][0];
    $line['product_id'] = $secondProduct->id;
    $line['quantity'] = 2;
    $payload['items'][] = $line;
    $order = $this->service->createOrder($payload);
    expect(fn () => $this->service->sendToStock($order->id))->toThrow(ValidationException::class);
    expect($this->product->fresh()->getStock($this->origin->id))->toBe(10)
        ->and($secondProduct->fresh()->getStock($this->origin->id))->toBe(1)
        ->and($this->product->fresh()->getStock($this->destinationA->id))->toBe(0)
        ->and($order->fresh()->items->every(fn ($item) => $item->stock_sent_at === null))->toBeTrue();
});

test('canceling or deleting pending transfers never adds source stock', function () {
    $order = $this->service->createOrder($this->payload);
    $this->service->cancelOrder($order->id);
    expect($this->product->fresh()->getStock($this->origin->id))->toBe(10);
    $this->service->deleteOrder($order->id);
    expect($this->product->fresh()->getStock($this->origin->id))->toBe(10);
});

test('destinations see their lines but cannot edit or execute the complete transfer', function () {
    $this->withoutVite();
    $order = $this->service->createOrder($this->payload);
    $recipient = User::factory()->create(['branch_id' => $this->destinationA->id]);
    $recipient->givePermissionTo(['orders.view', 'orders.approve', 'orders.update']);
    $this->actingAs($recipient);
    $this->get(route('web.orders.show', $order))->assertOk()->assertDontSee('Destino Salta B')
        ->assertDontSee('id="btn-send-to-stock"', false);
    $this->get(route('web.orders.edit', $order))->assertForbidden();
    $this->postJson(route('web.orders.send-to-stock', $order))->assertForbidden();
    expect($this->service->getPurchasedOrders()->pluck('id')->all())->toContain($order->id);
    $this->getJson('/api/orders/'.$order->id)->assertOk()
        ->assertJsonCount(1, 'items')->assertJsonPath('totals.1', 600);
    $outsider = User::factory()->create(['branch_id' => Branch::create(['name' => 'Ajena', 'province_id' => $this->targetProvince->id])->id]);
    $outsider->givePermissionTo('orders.view');
    $this->actingAs($outsider)->get(route('web.orders.show', $order))->assertForbidden();
});

test('provincial privileges cannot execute transfers belonging to another origin', function () {
    $this->withoutVite();
    $order = $this->service->createOrder($this->payload);
    $recipient = User::factory()->create(['branch_id' => $this->destinationA->id, 'province_id' => $this->targetProvince->id]);
    $recipient->assignRole(RoleLabel::PROVINCIAL_ADMIN->value);
    $this->actingAs($recipient);
    $this->get(route('web.orders.show', $order))->assertOk();
    $this->postJson(route('web.orders.send-to-stock', $order))->assertForbidden();
    $this->get(route('web.orders.edit', $order))->assertForbidden();
    expect($this->product->fresh()->getStock($this->origin->id))->toBe(10);
});

test('validation redirects preserve transfer cart rows and destinations', function () {
    $this->withoutVite();
    $payload = $this->payload;
    $payload['items'][0]['destination_province_id'] = $this->province->id;
    $this->from(route('web.orders.create-branch'))->post(route('web.orders.store'), $payload)
        ->assertRedirect(route('web.orders.create-branch'))->assertSessionHasErrors('items.0.destination_branch_id');
    $this->get(route('web.orders.create-branch'))->assertOk()->assertSee('TRANSFER-001');
});

test('inventory search cannot use an unrelated origin or list products absent from source', function () {
    $otherProduct = Product::create(['code' => 'OTHER-001', 'name' => 'Producto ajeno']);
    app(ProductStockService::class)->addStock($otherProduct, 5, $this->destinationA->id);
    $this->getJson('/api/inventory/list?'.http_build_query(['branch_id' => $this->origin->id, 'is_multi_destination' => 1]))
        ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $this->product->id);
    $this->getJson('/api/inventory/list?'.http_build_query(['branch_id' => $this->destinationA->id, 'is_multi_destination' => 1]))
        ->assertUnprocessable();
});

test('canceled transfers and removed destinations cannot receive stock', function () {
    $order = $this->service->createOrder($this->payload);
    $this->service->cancelOrder($order->id);
    expect(fn () => $this->service->sendToStock($order->id))->toThrow(ValidationException::class);
    $secondOrder = $this->service->createOrder($this->payload);
    $this->destinationA->delete();
    expect(fn () => $this->service->sendToStock($secondOrder->id))->toThrow(ValidationException::class);
    expect($this->product->fresh()->getStock($this->origin->id))->toBe(10);
});

test('notifications disclose only the destination quantity after a successful transaction', function () {
    $recipientA = User::factory()->create(['branch_id' => $this->destinationA->id]);
    $recipientB = User::factory()->create(['branch_id' => $this->destinationB->id]);
    $outsider = User::factory()->create(['branch_id' => null]);
    $order = $this->service->createOrder($this->payload);
    Notification::assertSentTo($recipientA, OrderStatusNotification::class,
        fn ($notification) => str_contains($notification->message, '3 unidades para recibir'));
    Notification::assertSentTo($recipientB, OrderStatusNotification::class,
        fn ($notification) => str_contains($notification->message, '2 unidades para recibir'));
    Notification::assertNotSentTo($outsider, OrderStatusNotification::class);
    Notification::fake();
    $this->service->sendToStock($order->id);
    Notification::assertSentTo($recipientA, OrderStatusNotification::class,
        fn ($notification) => str_contains($notification->message, 'Confirmado'));
    Notification::fake();
    expect(fn () => $this->service->sendToStock($order->id))->toThrow(ValidationException::class);
    Notification::assertNothingSent();
});

test('a product absent from origin cannot be submitted as a transfer line', function () {
    $other = Product::create(['code' => 'NO-ORIGIN', 'name' => 'Sin origen']);
    $payload = $this->payload;
    $payload['items'][0]['product_id'] = $other->id;
    expect(fn () => $this->service->createOrder($payload))->toThrow(ValidationException::class);
    expect(Order::count())->toBe(0);
});
