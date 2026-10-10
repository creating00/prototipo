<?php

use App\Models\Product;
use App\Models\Provider;
use App\Models\ProviderCatalog;
use App\Models\ProviderCatalogItem;
use App\Models\ProviderCatalogSnapshot;
use App\Models\User;
use App\Services\Catalogs\CatalogProcessingService;
use App\Services\Catalogs\ProductData;
use App\Services\ProviderService;
use Illuminate\Support\Facades\DB;
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

test('comparison provider filters include only saved catalogs and retain archived suppliers', function () {
    $user = historyCatalogUser();
    $service = app(CatalogProcessingService::class);
    $saved = historyCatalogPreview('Proveedor guardado', [ProductData::make(['name' => 'Cable'])]);
    $service->process($saved->provider_id, $saved->token, $user->id);
    $saved->provider->delete();
    historyCatalogPreview('Solo vista previa', [ProductData::make(['name' => 'Cable'])]);
    $response = $this->actingAs($user)->get(route('web.provider-catalogs.index'))->assertOk();
    $providers = $response->viewData('comparisonProviders');
    expect($providers->pluck('provider_id')->all())->toBe([$saved->provider_id]);
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
    $xpath = new DOMXPath($dom);
    expect($xpath->query('//select[contains(@class,"catalog-comparison-provider")]')->length)->toBe(8)
        ->and($xpath->query('//select[@id="compare-provider-0"]/option')->length)->toBe(2);
    $this->getJson(route('web.provider-catalogs.products', ['provider_id' => $saved->provider_id]))
        ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('options.0.provider', 'Proveedor guardado');
});

test('product search combines provider filters and text across every saved page', function () {
    $user = historyCatalogUser();
    $service = app(CatalogProcessingService::class);
    $products = [];
    for ($i = 0; $i < 55; $i++) {
        $products[] = ProductData::make(['name' => 'Cable '.$i, 'code' => 'CODE-'.$i, 'image' => 'https://cdn.example.com/cable.jpg']);
    }
    $a = historyCatalogPreview('A', $products);
    $service->process($a->provider_id, $a->token, $user->id);
    $b = historyCatalogPreview('B', [ProductData::make(['name' => 'Cable 54', 'code' => 'CODE-54'])]);
    $service->process($b->provider_id, $b->token, $user->id);
    $this->actingAs($user);
    $first = $this->getJson(route('web.provider-catalogs.products', ['provider_id' => $a->provider_id, 'q' => 'Cable']))
        ->assertOk()->assertJsonPath('total', 55)->assertJsonCount(50, 'options');
    parse_str(parse_url($first->json('next_page_url'), PHP_URL_QUERY), $next);
    expect($next['provider_id'])->toBe((string) $a->provider_id)->and($next['q'])->toBe('Cable');
    $last = $this->getJson($first->json('next_page_url'))->assertOk()->assertJsonCount(5, 'options');
    expect(array_column($last->json('options'), 'optgroup'))->each->toBe((string) $a->provider_id);
    $this->getJson(route('web.provider-catalogs.products', ['provider_id' => $a->provider_id, 'q' => 'CODE-54']))
        ->assertJsonPath('total', 1)->assertJsonPath('options.0.image', 'https://cdn.example.com/cable.jpg');
    $this->getJson(route('web.provider-catalogs.products', ['provider_id' => $b->provider_id, 'q' => 'CODE-54']))
        ->assertJsonPath('total', 1)->assertJsonPath('options.0.provider', 'B');
    $this->getJson(route('web.provider-catalogs.products', ['provider_id' => 999999]))->assertJsonPath('total', 0);
    $this->getJson(route('web.provider-catalogs.products', ['provider_id' => '']))->assertJsonPath('total', 56);
    $this->getJson(route('web.provider-catalogs.products', ['provider_id' => ['invalid']]))->assertUnprocessable();
    $this->actingAs(historyCatalogUser(['providers.view']))
        ->getJson(route('web.provider-catalogs.products', ['provider_id' => $a->provider_id]))->assertForbidden();
});

test('validation redirects restore each product with its own provider filter', function () {
    $user = historyCatalogUser();
    $ids = [];
    $providerIds = [];
    foreach (['A', 'B'] as $name) {
        $preview = historyCatalogPreview($name, [ProductData::make(['name' => 'Cable'])]);
        $snapshot = app(CatalogProcessingService::class)->process($preview->provider_id, $preview->token, $user->id);
        $ids[] = (string) $snapshot->products()->first()->id;
        $providerIds[] = $preview->provider_id;
    }
    $response = $this->actingAs($user)->withSession(['_old_input' => ['entries' => $ids]])
        ->get(route('web.provider-catalogs.index'))->assertOk();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
    $xpath = new DOMXPath($dom);
    foreach ($ids as $slot => $id) {
        expect($xpath->query('//select[@id="compare-entry-'.$slot.'"]/option')->length)->toBe(2)
            ->and($xpath->query('//select[@id="compare-entry-'.$slot.'"]/option[@selected]')->item(0)->getAttribute('value'))->toBe($id)
            ->and($xpath->query('//select[@id="compare-provider-'.$slot.'"]/option[@selected]')->item(0)->getAttribute('value'))->toBe((string) $providerIds[$slot]);
    }
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

test('confirmed catalog cleanup removes every preview and history but retains suppliers URLs and inventory', function () {
    $user = historyCatalogUser(['providers.view', 'providers.update', 'providers.delete', 'product_provider_prices.view']);
    $user->assignRole(\Spatie\Permission\Models\Role::findOrCreate('admin', 'web'));
    $internal = Product::create(['code' => 'INTERNAL-KEEP', 'name' => 'Producto del inventario']);
    $service = app(CatalogProcessingService::class);
    $first = historyCatalogPreview('Primero', [ProductData::make(['name' => 'Cable'])]);
    $second = historyCatalogPreview('Segundo', [ProductData::make(['name' => 'Fuente'])]);
    $service->process($first->provider_id, $first->token, $user->id);
    $service->process($second->provider_id, $second->token, $user->id);
    $second->provider->delete(); // Archived records from deleted suppliers are included.
    $this->actingAs($user)->delete(route('web.provider-catalogs.clear'), ['confirm_clear' => '1'])
        ->assertRedirect(route('web.provider-catalogs.index'))->assertSessionHas('success');
    expect(ProviderCatalog::count())->toBe(0)->and(ProviderCatalogItem::count())->toBe(0)
        ->and(ProviderCatalogSnapshot::count())->toBe(0)->and(Provider::withTrashed()->count())->toBe(2)
        ->and(Provider::find($first->provider_id)->catalog_url)->toBe($first->source_url)
        ->and(Product::find($internal->id)->name)->toBe('Producto del inventario');
    $this->getJson(route('web.provider-catalogs.products'))->assertOk()->assertJsonPath('total', 0);
});

test('catalog cleanup requires deletion price and view permissions', function (array $permissions) {
    $preview = historyCatalogPreview('Proveedor', [ProductData::make(['name' => 'Cable'])]);
    $user = historyCatalogUser($permissions);
    $this->actingAs($user)->deleteJson(route('web.provider-catalogs.clear'), ['confirm_clear' => '1'])->assertForbidden();
    expect($preview->fresh())->not->toBeNull();
})->with([
    'read only' => [['providers.view', 'product_provider_prices.view']],
    'update without delete' => [['providers.view', 'providers.update', 'product_provider_prices.view']],
    'delete without price access' => [['providers.view', 'providers.delete']],
    'delete without supplier view' => [['providers.delete', 'product_provider_prices.view']],
]);

test('catalog cleanup requires explicit confirmation and respects consolidated mode', function () {
    $user = historyCatalogUser(['providers.view', 'providers.delete', 'product_provider_prices.view']);
    $user->assignRole(\Spatie\Permission\Models\Role::findOrCreate('admin', 'web'));
    historyCatalogPreview('Proveedor', [ProductData::make(['name' => 'Cable'])]);
    $this->actingAs($user)->deleteJson(route('web.provider-catalogs.clear'), ['confirm_clear' => '0'])->assertUnprocessable();
    $this->deleteJson(route('web.provider-catalogs.clear'))->assertUnprocessable();
    $this->withSession(['active_branch_id' => 'all'])->deleteJson(route('web.provider-catalogs.clear'), ['confirm_clear' => '1'])->assertForbidden();
    expect(ProviderCatalog::count())->toBe(1);
    $this->get(route('web.provider-catalogs.index'))->assertOk()->assertDontSee('data-catalog-clear-form', false);
});

test('a catalog job finishing after cleanup cannot restore deleted results', function () {
    $preview = historyCatalogPreview('Proveedor', []);
    $preview->update(['status' => 'queued', 'completed_at' => null]);
    $registry = Mockery::mock(\App\Services\Catalogs\CatalogRegistry::class);
    $registry->shouldReceive('fetch')->once()->andReturnUsing(function () {
        app(CatalogProcessingService::class)->clear();

        return new \App\Services\Catalogs\CatalogResult([ProductData::make(['name' => 'Late result'])], 'partial', 'Old query');
    });
    $job = new \App\Jobs\FetchProviderCatalog($preview->provider_id, $preview->token);
    $job->handle($registry);
    $job->failed(new RuntimeException('Old notification'));
    expect(ProviderCatalog::count())->toBe(0)->and(ProviderCatalogItem::count())->toBe(0)->and(ProviderCatalogSnapshot::count())->toBe(0);
});

test('catalog cleanup rolls back every deletion if the history deletion fails', function () {
    $user = historyCatalogUser();
    $preview = historyCatalogPreview('Proveedor', [ProductData::make(['name' => 'Keep on failure'])]);
    $service = app(CatalogProcessingService::class);
    $service->process($preview->provider_id, $preview->token, $user->id);
    DB::listen(function ($query) {
        if (str_starts_with($query->sql, 'delete from "provider_catalog_snapshots"')) {
            throw new RuntimeException('Simulated deletion failure');
        }
    });
    expect(fn () => $service->clear())->toThrow(RuntimeException::class, 'Simulated deletion failure');
    expect(ProviderCatalog::count())->toBe(1)->and(ProviderCatalogItem::count())->toBe(1)->and(ProviderCatalogSnapshot::count())->toBe(1);
});

test('both catalog lists start collapsed and cleanup button is visible only to administrators', function () {
    $this->actingAs(historyCatalogUser())->get(route('web.provider-catalogs.index'))->assertOk()->assertDontSee('data-catalog-clear-form', false);
    $admin = historyCatalogUser(['providers.view', 'providers.delete', 'product_provider_prices.view']);
    $admin->assignRole(\Spatie\Permission\Models\Role::findOrCreate('admin', 'web'));
    $response = $this->actingAs($admin)
        ->get(route('web.provider-catalogs.index'))->assertOk()->assertSee('Limpiar todos los catálogos web');
    $xpath = ProductData::dom($response->getContent());
    expect($xpath->query('//details[@id="catalog-published-products" and not(@open)]/summary')->length)->toBe(1)
        ->and($xpath->query('//details[@id="catalog-history" and not(@open)]/summary')->length)->toBe(1);
});
