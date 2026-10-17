<?php

use App\Enums\CurrencyType;
use App\Enums\OrderStatus;
use App\Enums\RoleLabel;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\Province;
use App\Models\User;
use App\Services\OrderService;
use App\Services\Product\ProductStockService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Notification::fake();
    $this->withoutVite();
    Permission::findOrCreate('orders.approve');
    Permission::findOrCreate('orders.view');
    $role = Role::findOrCreate(RoleLabel::PROVINCIAL_ADMIN->value);
    $role->givePermissionTo(['orders.approve', 'orders.view']);
    $province = Province::create(['api_id' => '14', 'name' => 'Córdoba']);
    $otherProvince = Province::create(['api_id' => '66', 'name' => 'Salta']);
    $this->origin = Branch::create(['name' => 'Sucursal Córdoba', 'province_id' => $province->id]);
    $this->destination = Branch::create(['name' => 'General Paz', 'province_id' => $province->id]);
    $this->outsider = Branch::create(['name' => 'Sucursal Salta', 'province_id' => $otherProvince->id]);
    // The assigned branch deliberately differs from the active branch.
    $this->user = User::factory()->create(['branch_id' => $this->destination->id, 'province_id' => $province->id]);
    $this->user->assignRole($role);
    $this->actingAs($this->user)->withSession(['active_branch_id' => $this->origin->id]);
    $this->product = Product::create(['code' => 'CART-001', 'name' => 'Pantalla']);
    $stock = app(ProductStockService::class);
    $stock->addStock($this->product, 10, $this->origin->id);
    $stock->addStock($this->product, 3, $this->destination->id);
    $stock->updatePurchasePrice($this->product, $this->origin->id, 100, CurrencyType::USD->value);
    $stock->updatePurchasePrice($this->product, $this->destination->id, 150, CurrencyType::USD->value);
    $this->payload = [
        'branch_id' => $this->origin->id,
        'destination_branch_id' => $this->destination->id,
        'transfer_token' => (string) Str::uuid(),
        'items' => [['product_id' => $this->product->id, 'quantity' => 4, 'unit_price' => 9999, 'currency' => CurrencyType::ARS->value]],
    ];
    $this->url = route('web.sales.send-to-branch');
});

test('cart transfers immediately move stock without sales payments or price changes', function () {
    $this->postJson($this->url, $this->payload)->assertOk()
        ->assertJsonPath('order_number', 'PED-00000001');
    $order = Order::sole();
    expect($order->branch_id)->toBe($this->origin->id)
        ->and($order->user_id)->toBe($this->user->id)
        ->and($order->status)->toBe(OrderStatus::Confirmed)
        ->and($order->is_stock_sent)->toBeTrue()
        ->and($order->sale_id)->toBeNull()
        ->and($order->items->first()->stock_sent_at)->not->toBeNull()
        ->and((float) $order->items->first()->unit_price)->toBe(100.0)
        ->and($order->items->first()->currency)->toBe(CurrencyType::USD)
        ->and($this->product->fresh()->getStock($this->origin->id))->toBe(6)
        ->and($this->product->fresh()->getStock($this->destination->id))->toBe(7)
        ->and((float) $this->product->purchasePriceModel($this->destination->id)->amount)->toBe(150.0);
    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseCount('payments', 0);
    $this->assertDatabaseCount('product_branch_prices', 2);

    $service = app(OrderService::class);
    expect($service->getPurchasedOrders()->modelKeys())->toContain($order->id);
    $this->withSession(['active_branch_id' => $this->destination->id]);
    expect($service->getPurchasedOrders()->modelKeys())->toContain($order->id);
    $this->get(route('web.orders.purchases'))->assertOk()->assertSee($order->order_number);
    $this->withSession(['active_branch_id' => $this->origin->id]);
    expect(fn () => $service->sendToStock($order->id))->toThrow(ValidationException::class);
    expect(fn () => $service->convertToSale($order->id))->toThrow(ValidationException::class);
});

test('retrying the same cart only moves stock once', function () {
    $first = $this->postJson($this->url, $this->payload)->assertOk();
    $this->postJson($this->url, $this->payload)->assertOk()->assertExactJson($first->json());
    $this->assertDatabaseCount('orders', 1);
    expect($this->product->fresh()->getStock($this->origin->id))->toBe(6)
        ->and($this->product->fresh()->getStock($this->destination->id))->toBe(7);
});

test('a processed token cannot silently accept a changed cart', function () {
    $this->postJson($this->url, $this->payload)->assertOk();
    $this->payload['items'][0]['quantity'] = 2;
    $this->postJson($this->url, $this->payload)->assertUnprocessable()->assertJsonValidationErrors('transfer_token');
    $this->assertDatabaseCount('orders', 1);
    expect($this->product->fresh()->getStock($this->origin->id))->toBe(6);
});

test('invalid carts and destinations do not create orders or move stock', function (string $case, string $field) {
    $payload = $this->payload;
    match ($case) {
        'same branch' => $payload['destination_branch_id'] = $this->origin->id,
        'other province' => $payload['destination_branch_id'] = $this->outsider->id,
        'wrong origin' => $payload['branch_id'] = $this->destination->id,
        'deleted destination' => $this->destination->delete(),
        'empty' => $payload['items'] = [],
        'zero' => $payload['items'][0]['quantity'] = 0,
        'fractional' => $payload['items'][0]['quantity'] = 1.5,
        'duplicate' => $payload['items'][] = $payload['items'][0],
        'stock' => $payload['items'][0]['quantity'] = 11,
        'token' => $payload['transfer_token'] = '',
        'deleted product' => $this->product->delete(),
        'absent from origin' => $this->product->productBranches()->where('branch_id', $this->origin->id)->delete(),
        'consolidated' => $this->withSession(['active_branch_id' => 'all']),
        'forged context' => $this->withSession(['active_branch_id' => $this->outsider->id]),
    };
    $this->postJson($this->url, $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
    $this->assertDatabaseCount('orders', 0);
    expect($this->product->fresh()->getStock($this->origin->id))->toBe($case === 'absent from origin' ? 0 : 10);
})->with([
    ['same branch', 'destination_branch_id'], ['other province', 'destination_branch_id'],
    ['wrong origin', 'branch_id'], ['deleted destination', 'destination_branch_id'],
    ['empty', 'items'], ['zero', 'items.0.quantity'], ['fractional', 'items.0.quantity'],
    ['duplicate', 'items.0.product_id'], ['stock', 'stock'], ['token', 'transfer_token'],
    ['deleted product', 'items.0.product_id'], ['absent from origin', 'items.0.product_id'],
    ['consolidated', 'branch_id'], ['forged context', 'branch_id'],
]);

test('a stock failure on a later product rolls back the entire cart', function () {
    $product = Product::create(['code' => 'CART-002', 'name' => 'Batería']);
    app(ProductStockService::class)->addStock($product, 1, $this->origin->id);
    $this->payload['items'][] = ['product_id' => $product->id, 'quantity' => 2];
    $this->postJson($this->url, $this->payload)->assertUnprocessable();
    $this->assertDatabaseCount('orders', 0);
    $this->assertDatabaseCount('order_items', 0);
    expect($this->product->fresh()->getStock($this->origin->id))->toBe(10)
        ->and($this->product->fresh()->getStock($this->destination->id))->toBe(3)
        ->and($product->fresh()->getStock($this->origin->id))->toBe(1);
});

test('only provincial users can send a cart and the page limits its destinations', function () {
    $this->get(route('web.sales.create-client'))->assertOk()->assertSee('id="send-to-branch"', false)
        ->assertViewHas('cartTransferBranches', fn ($branches) => $branches->modelKeys() === [$this->destination->id]);
    $seller = User::factory()->create(['branch_id' => $this->origin->id]);
    $seller->givePermissionTo(Permission::findOrCreate('sales.create_client'));
    $this->actingAs($seller)->postJson($this->url, $this->payload)->assertForbidden();
    $this->get(route('web.sales.create-client'))->assertOk()->assertDontSee('id="send-to-branch"', false);
    $this->assertDatabaseCount('orders', 0);
});

test('a cart can introduce a product to the receiving branch without inventing a cost', function () {
    $destination = Branch::create(['name' => 'Cofico', 'province_id' => $this->origin->province_id]);
    $this->payload['destination_branch_id'] = $destination->id;
    $this->postJson($this->url, $this->payload)->assertOk();
    expect($this->product->fresh()->getStock($destination->id))->toBe(4)
        ->and($this->product->purchasePriceModel($destination->id))->toBeNull();
});
