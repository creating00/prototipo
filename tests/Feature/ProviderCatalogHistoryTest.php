<?php

use App\Models\Provider;
use App\Models\ProviderCatalog;
use App\Models\ProviderCatalogItem;
use App\Models\ProviderCatalogSnapshot;
use App\Models\User;
use App\Services\Catalogs\CatalogProcessingService;
use App\Services\Catalogs\ProductData;
use App\Services\ProviderService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

function historyCatalogUser(array $permissions = ['providers.view', 'providers.update', 'product_provider_prices.view']): User
{
    $user = User::factory()->create();
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $user->givePermissionTo($permissions);

    return $user;
}

function historyCatalogPreview(string $providerName, array $products): ProviderCatalog
{
    $provider = Provider::create(['business_name' => $providerName, 'tax_id' => (string) Str::uuid(), 'catalog_url' => 'https://www.i2cmayorista.com/']);

    return ProviderCatalog::create([
        'provider_id' => $provider->id, 'token' => (string) Str::uuid(), 'status' => 'partial',
        'source_url' => $provider->catalog_url, 'products' => $products,
        'message' => 'Solo página consultada', 'requested_at' => now()->subMinutes(2), 'completed_at' => now()->subMinute(),
    ]);
}

test('query results are previews until processing saves every product exactly once', function () {
    $user = historyCatalogUser();
    $products = [ProductData::make(['name' => 'Cable', 'price' => '0', 'image' => 'https://cdn.example.com/cable.jpg']), ProductData::make(['name' => 'Fuente', 'price' => '20', 'currency' => 'ARS'])];
    $preview = historyCatalogPreview('Primero', $products);
    $this->actingAs($user)->getJson(route('web.provider-catalogs.products'))->assertOk()->assertJsonPath('total', 0);
    $this->get(route('web.provider-catalogs.index', ['provider' => $preview->provider_id]))
        ->assertOk()->assertSee('Procesar')->assertSee('Vista previa:')->assertSee('data-catalog-provider-form', false)->assertDontSee('>Ver</button>', false);
    $this->post(route('web.providers.catalog.process', $preview->provider_id), ['token' => $preview->token])->assertRedirect()->assertSessionHas('success');
    $snapshot = ProviderCatalogSnapshot::first();
    expect($snapshot->status)->toBe('partial')->and($snapshot->message)->toBe('Solo página consultada')
        ->and($snapshot->processed_by)->toBe($user->id)->and($snapshot->queried_at->equalTo($preview->completed_at))->toBeTrue()
        ->and($snapshot->products()->orderBy('position')->pluck('data')->all())->toBe($products);
    $this->post(route('web.providers.catalog.process', $preview->provider_id), ['token' => $preview->token])->assertRedirect();
    expect(ProviderCatalogSnapshot::count())->toBe(1)->and(ProviderCatalogItem::count())->toBe(2);
    $this->getJson(route('web.provider-catalogs.products'))->assertJsonPath('total', 2)
        ->assertJsonPath('options.0.image', 'https://cdn.example.com/cable.jpg')->assertJsonPath('options.0.price', '0')->assertJsonPath('options.0.currency', null);
    $this->get(route('web.provider-catalogs.history', $snapshot))->assertOk()->assertSee('Cable')->assertSee('Registro #'.$snapshot->id)->assertSee('loading="lazy"', false);
});

test('new previews and URL edits keep all processed versions available for comparison', function () {
    $user = historyCatalogUser();
    $preview = historyCatalogPreview('Primero', [ProductData::make(['name' => 'Old cable', 'price' => '10'])]);
    $service = app(CatalogProcessingService::class);
    $first = $service->process($preview->provider_id, $preview->token, $user->id);
    $firstItem = $first->products()->first();
    config(['queue.default' => 'database']);
    Queue::fake();
    $this->actingAs($user)->post(route('web.providers.catalog.refresh', $preview->provider_id))->assertRedirect();
    expect($firstItem->fresh()->data['price'])->toBe('10');
    $preview->refresh()->update(['status' => 'complete', 'products' => [ProductData::make(['name' => 'New cable', 'price' => '20'])], 'completed_at' => now()]);
    $second = $service->process($preview->provider_id, $preview->fresh()->token, $user->id);
    $provider = $preview->provider;
    app(ProviderService::class)->updateProvider($provider->id, ['business_name' => 'Renamed provider', 'tax_id' => $provider->tax_id, 'catalog_url' => 'https://unvarg.com/search/?q=lenovo']);
    expect(ProviderCatalog::count())->toBe(0)->and(ProviderCatalogSnapshot::count())->toBe(2)->and($firstItem->fresh()->data['price'])->toBe('10');
    $this->getJson(route('web.provider-catalogs.products'))->assertJsonPath('total', 2)->assertJsonPath('options.0.snapshot_id', $second->id);
    $this->get(route('web.provider-catalogs.history', $first))->assertOk()->assertSee('Primero')->assertSee('Old cable');
    $provider->delete();
    $this->get(route('web.provider-catalogs.history', $first))->assertOk();
});

test('all processed providers appear as optgroups with dates and safe images', function () {
    $user = historyCatalogUser();
    $service = app(CatalogProcessingService::class);
    foreach (['Proveedor A', 'Proveedor B'] as $name) {
        $preview = historyCatalogPreview($name, [ProductData::make(['name' => 'Same name', 'code' => 'SKU', 'price' => '10', 'image' => 'https://cdn.example.com/photo.jpg'])]);
        $service->process($preview->provider_id, $preview->token, $user->id);
    }
    historyCatalogPreview('Not processed', [ProductData::make(['name' => 'Pending item'])]);
    $response = $this->actingAs($user)->getJson(route('web.provider-catalogs.products'))->assertOk()->assertJsonPath('total', 2)->assertJsonCount(2, 'optgroups');
    expect(array_column($response->json('optgroups'), 'label'))->toBe(['Proveedor A', 'Proveedor B']);
    expect(array_column($response->json('options'), 'provider'))->toBe(['Proveedor A', 'Proveedor B']);
    expect($response->json('options.0.queried_at'))->not->toBeEmpty();
    $this->getJson(route('web.provider-catalogs.products', ['q' => 'Proveedor B']))->assertJsonPath('total', 1)->assertJsonPath('options.0.provider', 'Proveedor B');
});

test('comparison uses saved item identities and survives refreshes from both suppliers', function () {
    $user = historyCatalogUser();
    $service = app(CatalogProcessingService::class);
    $items = [];
    foreach (['Proveedor A', 'Proveedor B'] as $name) {
        $preview = historyCatalogPreview($name, [ProductData::make(['name' => 'Producto equivalente', 'price' => '50', 'currency' => 'ARS'])]);
        $snapshot = $service->process($preview->provider_id, $preview->token, $user->id);
        $items[] = (string) $snapshot->products()->first()->id;
        $preview->update(['token' => (string) Str::uuid(), 'status' => 'failed', 'products' => []]);
    }
    $this->actingAs($user)->post(route('web.provider-catalogs.compare'), ['entries' => $items, 'equivalence_reviewed' => '1'])
        ->assertOk()->assertSee('Proveedor A')->assertSee('Proveedor B')->assertSee('Precios guardados en el historial');
    $this->postJson(route('web.provider-catalogs.compare'), ['entries' => $items])->assertUnprocessable();
    $this->postJson(route('web.provider-catalogs.compare'), ['entries' => [$items[0], '9999999'], 'equivalence_reviewed' => '1'])->assertUnprocessable();
});

test('processing rejects stale queued failed empty or invalid previews', function (string $status, bool $empty, bool $stale) {
    $user = historyCatalogUser();
    $preview = historyCatalogPreview('Proveedor', $empty ? [] : [ProductData::make(['name' => 'Item'])]);
    $preview->update(['status' => $status]);
    $this->actingAs($user)->postJson(route('web.providers.catalog.process', $preview->provider_id), ['token' => $stale ? (string) Str::uuid() : $preview->token])->assertUnprocessable();
    expect(ProviderCatalogSnapshot::count())->toBe(0)->and(ProviderCatalogItem::count())->toBe(0);
})->with([['queued', false, false], ['failed', false, false], ['unsupported', false, false], ['complete', true, false], ['partial', false, true]]);

test('processing permissions and consolidated branch restrictions are enforced', function () {
    $preview = historyCatalogPreview('Proveedor', [ProductData::make(['name' => 'Item'])]);
    $this->actingAs(historyCatalogUser(['providers.view', 'product_provider_prices.view']))
        ->postJson(route('web.providers.catalog.process', $preview->provider_id), ['token' => $preview->token])->assertForbidden();
    $this->actingAs(historyCatalogUser(['providers.view']))->getJson(route('web.provider-catalogs.products'))->assertForbidden();
    $this->actingAs(historyCatalogUser())->withSession(['active_branch_id' => 'all'])
        ->postJson(route('web.providers.catalog.process', $preview->provider_id), ['token' => $preview->token])->assertForbidden();
    expect(ProviderCatalogSnapshot::count())->toBe(0);
});

test('processed product pagination reaches every item and searches codes', function () {
    $user = historyCatalogUser();
    $products = [];
    for ($position = 0; $position < 110; $position++) {
        $products[] = ProductData::make(['name' => 'Product '.$position, 'code' => 'CODE-'.$position]);
    }
    $preview = historyCatalogPreview('Proveedor', $products);
    app(CatalogProcessingService::class)->process($preview->provider_id, $preview->token, $user->id);
    $this->actingAs($user);
    $seen = [];
    for ($page = 1; $page <= 3; $page++) {
        $response = $this->getJson(route('web.provider-catalogs.products', ['page' => $page]))->assertOk()->assertJsonPath('total', 110);
        $seen = array_merge($seen, array_column($response->json('options'), 'value'));
        expect($response->json('next_page_url') === null)->toBe($page === 3);
    }
    expect(array_unique($seen))->toHaveCount(110);
    $this->getJson(route('web.provider-catalogs.products', ['q' => 'CODE-109']))->assertJsonPath('total', 1)->assertJsonPath('options.0.code', 'CODE-109');
});

test('processing is atomic even if a malformed product appears after the first inserted batch', function () {
    $user = historyCatalogUser();
    $rows = array_fill(0, 150, ProductData::make(['name' => 'Item']));
    $rows[] = 'invalid';
    $preview = historyCatalogPreview('Proveedor', $rows);
    expect(fn () => app(CatalogProcessingService::class)->process($preview->provider_id, $preview->token, $user->id))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
    expect(ProviderCatalogSnapshot::count())->toBe(0)->and(ProviderCatalogItem::count())->toBe(0);
});

test('history views and option responses do not expose unsafe image or product URLs', function () {
    $user = historyCatalogUser();
    $preview = historyCatalogPreview('Proveedor', [['name' => '<script>alert(1)</script>', 'image' => 'javascript:alert(1)', 'url' => 'javascript:alert(2)', 'details' => ['raw' => '<script>bad</script>']]]);
    $snapshot = app(CatalogProcessingService::class)->process($preview->provider_id, $preview->token, $user->id);
    $this->actingAs($user)->getJson(route('web.provider-catalogs.products'))->assertJsonPath('options.0.image', null);
    $this->get(route('web.provider-catalogs.history', $snapshot))->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('href="javascript:', false);
    $this->actingAs(historyCatalogUser(['providers.view']))->get(route('web.provider-catalogs.history', $snapshot))->assertForbidden();
});
