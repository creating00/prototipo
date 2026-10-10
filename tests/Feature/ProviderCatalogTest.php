<?php

use App\Jobs\FetchProviderCatalog;
use App\Models\Provider;
use App\Models\ProviderCatalog;
use App\Models\User;
use App\Services\Catalogs\CatalogHttpClient;
use App\Services\Catalogs\CatalogRegistry;
use App\Services\Catalogs\CatalogResult;
use App\Services\Catalogs\GoogleSheetAdapter;
use App\Services\Catalogs\PublicUrl;
use App\Services\Catalogs\TiendaNubeAdapter;
use App\Services\Catalogs\WooCommerceAdapter;
use App\Services\ProviderService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

function catalogUser(array $permissions = ['providers.view', 'providers.update', 'product_provider_prices.view']): User
{
    $user = User::factory()->create();
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $user->givePermissionTo($permissions);

    return $user;
}

function catalogProvider(string $name = 'Proveedor'): Provider
{
    return Provider::create(['business_name' => $name, 'tax_id' => (string) Str::uuid(), 'catalog_url' => 'https://www.i2cmayorista.com/']);
}

function catalogSnapshot(Provider $provider, array $products): ProviderCatalog
{
    return ProviderCatalog::create([
        'provider_id' => $provider->id, 'token' => (string) Str::uuid(), 'status' => 'partial',
        'source_url' => $provider->catalog_url, 'products' => $products, 'requested_at' => now(), 'completed_at' => now(),
    ]);
}

test('catalog routes require provider and purchase price permissions', function () {
    $provider = catalogProvider();
    $this->actingAs(catalogUser(['providers.view']))->get(route('web.provider-catalogs.index'))->assertForbidden();
    $this->post(route('web.providers.catalog.refresh', $provider))->assertForbidden();
    $this->post(route('web.provider-catalogs.compare'))->assertForbidden();
    $this->actingAs(catalogUser(['product_provider_prices.view']))->get(route('web.provider-catalogs.index'))->assertForbidden();
});

test('a read only user cannot request a catalog refresh', function () {
    $this->actingAs(catalogUser(['providers.view', 'product_provider_prices.view']))
        ->post(route('web.providers.catalog.refresh', catalogProvider()))->assertForbidden();
});

test('catalog refresh queues once and replaces old results', function () {
    config(['queue.default' => 'database']);
    Queue::fake();
    $provider = catalogProvider();
    catalogSnapshot($provider, [['name' => 'Old product']]);
    $this->actingAs(catalogUser())->post(route('web.providers.catalog.refresh', $provider))->assertRedirect();
    expect(ProviderCatalog::first()->status)->toBe('queued')->and(ProviderCatalog::first()->products)->toBe([]);
    Queue::assertPushed(FetchProviderCatalog::class, 1);
    $this->postJson(route('web.providers.catalog.refresh', $provider))->assertUnprocessable();
    Queue::assertPushed(FetchProviderCatalog::class, 1);
});

test('a catalog URL change invalidates the previous snapshot', function () {
    $provider = catalogProvider();
    catalogSnapshot($provider, []);
    app(ProviderService::class)->updateProvider($provider->id, [
        'business_name' => $provider->business_name, 'tax_id' => $provider->tax_id, 'catalog_url' => 'https://unvarg.com/search/?q=lenovo',
    ]);
    expect(ProviderCatalog::count())->toBe(0);
});

test('URL validation rejects credentials internal addresses schemes and nonstandard ports', function (string $url) {
    expect(fn () => app(ProviderService::class)->validateProviderData([
        'business_name' => 'Test', 'tax_id' => 'unique', 'catalog_url' => $url,
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);
})->with(['http://127.0.0.1/', 'http://[::1]/', 'http://localhost/', 'http://10.0.0.1/', 'file:///etc/passwd', 'https://user:pass@public.example.com/', 'https://public.example.com:8443/']);

test('reserved addresses are never public', function (string $ip) {
    expect(app(PublicUrl::class)->isPublicAddress($ip))->toBeFalse();
})->with(['127.0.0.1', '10.1.1.1', '169.254.169.254', '100.64.0.1', '192.0.2.1', '198.18.0.1', '224.0.0.1', '::1', '::ffff:8.8.8.8', 'fc00::1', '64:ff9b::a00:1', '2001:db8::1']);

test('HTTP redirects cannot reach another host or an internal address', function () {
    $urls = Mockery::mock(PublicUrl::class)->makePartial();
    $urls->shouldReceive('addresses')->once()->with('www.i2cmayorista.com')->andReturn(['8.8.8.8']);
    Http::fake(['https://www.i2cmayorista.com/' => Http::response('', 302, ['Location' => 'http://127.0.0.1/private'])]);
    expect(fn () => (new CatalogHttpClient($urls))->get('https://www.i2cmayorista.com/', ['www.i2cmayorista.com']))
        ->toThrow(\InvalidArgumentException::class);
    Http::assertSentCount(1);
});

test('HTTP download limits and access errors are explicit', function () {
    $urls = Mockery::mock(PublicUrl::class)->makePartial();
    $urls->shouldReceive('addresses')->andReturn(['8.8.8.8']);
    Http::fake(['*' => Http::sequence()->push(str_repeat('x', 2_000_001))->push('', 403)]);
    expect(fn () => (new CatalogHttpClient($urls))->get('https://www.i2cmayorista.com/', ['www.i2cmayorista.com']))->toThrow(RuntimeException::class);
    expect(fn () => (new CatalogHttpClient($urls))->get('https://www.i2cmayorista.com/', ['www.i2cmayorista.com']))->toThrow(RuntimeException::class, 'HTTP 403');
});

test('structured extraction preserves zero price absent fields and source details', function () {
    $html = '<script type="application/ld+json">'.json_encode([
        '@type' => 'Product', 'name' => 'Same name', 'sku' => 'ABC', 'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'], 'color' => 'Blue',
    ]).'</script>';
    $products = app(TiendaNubeAdapter::class)->parse($html);
    expect($products)->toHaveCount(1)->and($products[0]['price'])->toBe('0')
        ->and($products[0]['brand'])->toBeNull()->and($products[0]['model'])->toBeNull()
        ->and($products[0]['details']['color'])->toBe('Blue');
});

test('WooCommerce prices use explicitly published minor units and preserve variants', function () {
    $products = app(WooCommerceAdapter::class)->parse([[
        'id' => 1, 'name' => 'Item', 'prices' => ['price' => '900000', 'currency_minor_unit' => 2, 'currency_code' => 'ARS'], 'variations' => [['id' => 2]],
    ]]);
    expect($products[0]['price'])->toBe('9000.00')->and($products[0]['details']['variations'])->toHaveCount(1);
});

test('Fast Cheap cards handle sale prices ranges codes and unknown currency', function () {
    $html = '<div class="product type-product instock"><h3 class="product-title"><a href="https://www.fast-cheap.com.ar/producto/a/">A</a></h3><span class="price"><del><span class="woocommerce-Price-amount">$20,000.00</span></del><ins><span class="woocommerce-Price-amount">$15,000.00</span></ins></span><a data-product_sku="SKU-A"></a></div>';
    $rows = app(\App\Services\Catalogs\FastCheapAdapter::class)->parse($html);
    expect($rows[0]['price'])->toBe('15000.00')->and($rows[0]['code'])->toBe('SKU-A')->and($rows[0]['currency'])->toBeNull();
    $html = str_replace(['<del>', '</del>', '<ins>', '</ins>'], '', $html);
    expect(app(\App\Services\Catalogs\FastCheapAdapter::class)->parse($html)[0]['price'])->toBeNull();
});

test('WooCommerce keeps successful pages when a later page fails', function () {
    $http = Mockery::mock(CatalogHttpClient::class);
    $http->shouldReceive('get')->once()->withArgs(fn ($url) => str_contains($url, 'page=1'))
        ->andReturn(['body' => json_encode([['id' => 1, 'name' => 'Item', 'prices' => []]]), 'total_pages' => '2']);
    $http->shouldReceive('get')->once()->withArgs(fn ($url) => str_contains($url, 'page=2'))->andThrow(new RuntimeException('HTTP 403'));
    $result = (new WooCommerceAdapter($http))->fetch('https://syrcelulares.com/');
    expect($result->status)->toBe('partial')->and($result->products)->toHaveCount(1);
    expect($result->message)->toContain('HTTP 403');
});

test('sheet adapter parses quoted cells decimals and does not infer currency', function () {
    $products = app(GoogleSheetAdapter::class)->parse('Title,,,'."\n".'"Modulo, azul","$14.500,50",,Other,$20.000', 'https://docs.google.com/');
    expect($products)->toHaveCount(2)->and($products[0]['name'])->toBe('Modulo, azul')
        ->and($products[0]['price'])->toBe('14500.50')->and($products[0]['currency'])->toBeNull();
});

test('job persists results and ignores superseded request tokens', function () {
    $provider = catalogProvider();
    $snapshot = catalogSnapshot($provider, []);
    $snapshot->update(['status' => 'queued']);
    $registry = Mockery::mock(CatalogRegistry::class);
    $registry->shouldReceive('fetch')->once()->andReturn(new CatalogResult([['name' => 'New']], 'partial', 'Partial'));
    (new FetchProviderCatalog($provider->id, $snapshot->token))->handle($registry);
    expect($snapshot->fresh()->products)->toBe([['name' => 'New']])->and($snapshot->fresh()->status)->toBe('partial');
    (new FetchProviderCatalog($provider->id, (string) Str::uuid()))->handle($registry);
});

test('job failure clears products and reports error', function () {
    $provider = catalogProvider();
    $snapshot = catalogSnapshot($provider, []);
    $snapshot->update(['status' => 'queued']);
    $registry = Mockery::mock(CatalogRegistry::class);
    $registry->shouldReceive('fetch')->andThrow(new RuntimeException('HTTP 403'));
    (new FetchProviderCatalog($provider->id, $snapshot->token))->handle($registry);
    expect($snapshot->fresh()->status)->toBe('failed')->and($snapshot->fresh()->message)->toContain('HTTP 403');
});

test('comparison requires explicit review distinct providers and current tokens', function () {
    $product = \App\Services\Catalogs\ProductData::make(['name' => 'Identical name', 'price' => '20', 'currency' => 'ARS']);
    $first = catalogSnapshot(catalogProvider('First'), [$product]);
    $second = catalogSnapshot(catalogProvider('Second'), [$product]);
    $entries = ["{$first->id}:{$first->token}:0", "{$second->id}:{$second->token}:0"];
    $this->actingAs(catalogUser())->postJson(route('web.provider-catalogs.compare'), ['entries' => $entries])->assertUnprocessable();
    $this->post(route('web.provider-catalogs.compare'), ['entries' => [...$entries, ''], 'equivalence_reviewed' => '1'])->assertOk()->assertSee('First')->assertSee('Second');
    $second->update(['token' => (string) Str::uuid()]);
    $this->postJson(route('web.provider-catalogs.compare'), ['entries' => $entries, 'equivalence_reviewed' => '1'])->assertUnprocessable();
});

test('catalog page renders escaped source data and explicit pending integration', function () {
    $provider = catalogProvider();
    $snapshot = catalogSnapshot($provider, [\App\Services\Catalogs\ProductData::make(['name' => '<img src=x onerror=alert(1)>Danger', 'price' => '1', 'details' => ['raw' => '<script>alert(1)</script>']])]);
    $this->actingAs(catalogUser())->get(route('web.provider-catalogs.index', ['provider' => $provider->id]))
        ->assertOk()->assertSee('Equivalencia: requiere revisión')->assertDontSee('<script>alert(1)</script>', false);
    expect(app(CatalogRegistry::class)->fetch('https://catalog.example.com/')->status)->toBe('unsupported');
});
