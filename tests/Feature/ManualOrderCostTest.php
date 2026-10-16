<?php

use App\Enums\CurrencyType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PriceType;
use App\Enums\RoleLabel;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\Province;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Notification::fake();
    $province = Province::create(['api_id' => '14', 'name' => 'Cordoba']);
    $this->branch = Branch::create(['name' => 'Origen', 'province_id' => $province->id]);
    $this->destination = Branch::create(['name' => 'Destino', 'province_id' => $province->id]);
    $permission = Permission::findOrCreate('orders.create_client');
    foreach ([RoleLabel::ADMIN, RoleLabel::PROVINCIAL_ADMIN] as $roleLabel) {
        Role::findOrCreate($roleLabel->value)->givePermissionTo($permission);
    }
    $this->user = User::factory()->create([
        'branch_id' => $this->branch->id, 'province_id' => $province->id,
    ]);
    $this->user->assignRole(RoleLabel::ADMIN->value);
    $this->actingAs($this->user)->withSession(['active_branch_id' => $this->branch->id]);
    $this->product = Product::create(['code' => 'MSA150RI', 'name' => 'Modulo Samsung A15']);
    $this->productBranch = ProductBranch::create([
        'product_id' => $this->product->id, 'branch_id' => $this->branch->id,
        'stock' => 2, 'status' => 1,
    ]);
    $this->productBranch->prices()->create([
        'type' => PriceType::PURCHASE->value, 'currency' => CurrencyType::USD->value, 'amount' => 45,
    ]);
    $this->productBranch->prices()->create([
        'type' => PriceType::SALE->value, 'currency' => CurrencyType::ARS->value, 'amount' => 100000,
    ]);
    $client = Client::create([
        'branch_id' => $this->branch->id, 'full_name' => 'Cliente', 'document' => '22333444',
    ]);
    $this->payload = [
        'branch_id' => $this->branch->id, 'client_id' => $client->id,
        'customer_type' => Client::class, 'source' => OrderSource::Manual->value,
        'user_id' => $this->user->id, 'status' => OrderStatus::Pending->value, 'exchange_rate' => 1545,
        'items' => [[
            'product_id' => $this->product->id, 'quantity' => 2,
            'unit_price' => 100000, 'currency' => CurrencyType::ARS->value,
        ]],
    ];
});

test('manual order product rows use purchase cost and its currency', function (string $role) {
    $this->user->syncRoles($role);
    $response = $this->getJson('/api/inventory/by-code?'.http_build_query([
        'code' => $this->product->code, 'branch_id' => $this->branch->id,
        'context' => 'order', 'customer_type' => Client::class,
        'is_multi_destination' => $role === RoleLabel::PROVINCIAL_ADMIN->value ? 1 : 0,
        'order_source' => OrderSource::Manual->value,
    ]))->assertOk()->assertJsonPath('product.currency.code', 'USD');

    expect((float) $response->json('product.sale_price'))->toBe(45.0);
    $html = $response->json('html');
    expect($html)->toContain('name="items[INDEX][unit_price]"', 'value="45.00"')
        ->toContain('name="items[INDEX][currency]" value="2"')
        ->not->toContain('value="100000.00"');
})->with([RoleLabel::ADMIN->value, RoleLabel::PROVINCIAL_ADMIN->value]);

test('sales still use the retail price and currency', function () {
    $response = $this->getJson('/api/inventory/by-code?'.http_build_query([
        'code' => $this->product->code, 'branch_id' => $this->branch->id, 'context' => 'sale',
    ]))->assertOk()->assertJsonPath('product.currency.code', 'ARS');

    expect((float) $response->json('product.sale_price'))->toBe(100000.0);
});

test('manual orders never substitute retail price for a missing purchase cost', function () {
    $this->productBranch->prices()->where('type', PriceType::PURCHASE->value)->delete();
    $response = $this->getJson('/api/inventory/by-code?'.http_build_query([
        'code' => $this->product->code, 'branch_id' => $this->branch->id,
        'context' => 'order', 'customer_type' => Client::class,
    ]))->assertOk();
    expect((float) $response->json('product.sale_price'))->toBe(0.0);

    $order = app(OrderService::class)->createOrder($this->payload);
    expect((float) $order->items->first()->unit_price)->toBe(0.0);
});

test('saving manual orders enforces purchase cost and currency for regular admins', function (bool $multiDestination) {
    $payload = $this->payload;
    if ($multiDestination) {
        $payload['is_multi_destination'] = 1;
        $payload['target_branch_id'] = $this->destination->id;
        $payload['items'][0]['destination_branch_id'] = $this->destination->id;
        $payload['items'][0]['destination_province_id'] = $this->destination->province_id;
    }

    $service = app(OrderService::class);
    $order = $service->createOrder($payload);
    $item = $order->items->first();
    expect((float) $item->unit_price)->toBe(45.0)
        ->and($item->currency)->toBe(CurrencyType::USD)
        ->and((float) $item->subtotal)->toBe(90.0)
        ->and((float) $order->totals[CurrencyType::USD->value])->toBe(90.0);

    // Editing a saved order preserves its agreed cost even if the product cost changes.
    $this->productBranch->prices()->where('type', PriceType::PURCHASE->value)->update(['amount' => 60]);
    $payload['items'][0]['quantity'] = 3;
    $updated = $service->updateOrder($order->id, $payload);
    expect((float) $updated->items->first()->unit_price)->toBe(45.0)
        ->and($updated->items->first()->currency)->toBe(CurrencyType::USD)
        ->and((float) $updated->totals[CurrencyType::USD->value])->toBe(135.0);
})->with([false, true]);

test('provincial admins retain permission to enter a custom manual order cost', function () {
    $this->user->syncRoles(RoleLabel::PROVINCIAL_ADMIN->value);
    $payload = $this->payload;
    $payload['items'][0]['unit_price'] = 50;
    $payload['items'][0]['currency'] = CurrencyType::USD->value;

    $order = app(OrderService::class)->createOrder($payload);
    expect((float) $order->items->first()->unit_price)->toBe(50.0)
        ->and($order->items->first()->currency)->toBe(CurrencyType::USD);
});
