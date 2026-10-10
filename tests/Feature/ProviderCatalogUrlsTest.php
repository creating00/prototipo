<?php

use App\Jobs\FetchProviderCatalog;
use App\Models\Provider;
use App\Models\ProviderCatalog;
use App\Models\ProviderCatalogItem;
use App\Models\ProviderCatalogSnapshot;
use App\Services\Catalogs\CatalogProcessingService;
use App\Services\Catalogs\CatalogRegistry;
use App\Services\Catalogs\ProductData;
use App\Services\ProviderService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

function categoryCatalogProvider(): Provider
{
    return Provider::create([
        'business_name' => 'Fast categorías', 'tax_id' => (string) Str::uuid(),
        'catalog_url' => 'https://www.fast-cheap.com.ar/categorias/modulo/',
        'catalog_urls' => ['https://www.fast-cheap.com.ar/categorias/bateria/', 'https://www.fast-cheap.com.ar/categorias/flex/'],
    ]);
}

function categoryCatalogPreview(Provider $provider, string $url, string $name): ProviderCatalog
{
    return ProviderCatalog::create([
        'provider_id' => $provider->id, 'token' => (string) Str::uuid(), 'source_url' => $url,
        'status' => 'partial', 'products' => [ProductData::make(['name' => $name, 'price' => '10', 'url' => $url])],
        'requested_at' => now(), 'completed_at' => now(),
    ]);
}

test('catalog supplier selector includes primary and category URLs and excludes unconfigured suppliers', function () {
    $primary = Provider::create(['business_name' => 'Solo principal', 'tax_id' => 'primary', 'catalog_url' => 'https://unvarg.com/']);
    $categories = Provider::create(['business_name' => 'Solo categorías', 'tax_id' => 'categories', 'catalog_urls' => ['https://www.fast-cheap.com.ar/categorias/flex/']]);
    $empty = Provider::create(['business_name' => 'Sin catálogo', 'tax_id' => 'empty', 'catalog_url' => '', 'catalog_urls' => []]);
    Provider::create(['business_name' => 'URLs en blanco', 'tax_id' => 'blank', 'catalog_url' => '   ', 'catalog_urls' => ['', ' ']]);
    Provider::create(['business_name' => 'URLs nulas', 'tax_id' => 'null']);
    $response = $this->actingAs(catalogUser())->get(route('web.provider-catalogs.index', ['provider' => $empty->id]));
    $response->assertOk()->assertViewHas('providers', fn ($providers) => $providers->modelKeys() === [$categories->id, $primary->id])
        ->assertViewHas('selectedProvider', fn ($selected) => $selected->id === $categories->id);
    $xpath = ProductData::dom($response->getContent());
    expect($xpath->query('//select[@id="catalog-provider"]/option')->length)->toBe(2);
    $this->get(route('web.provider-catalogs.index', ['provider' => $primary->id]))
        ->assertOk()->assertViewHas('selectedProvider', fn ($selected) => $selected->id === $primary->id);
});

test('catalog page explains when suppliers exist but none has a catalog URL', function () {
    Provider::create(['business_name' => 'Proveedor sin URL', 'tax_id' => 'no-url']);
    $this->actingAs(catalogUser())->get(route('web.provider-catalogs.index'))->assertOk()
        ->assertSee('No hay proveedores con URLs de catálogo configuradas.')
        ->assertViewHas('providers', fn ($providers) => $providers->isEmpty())
        ->assertViewHas('selectedProvider', null);
});

test('provider forms store multiple category URLs and preserve the primary URL', function () {
    $user = catalogUser(['providers.view', 'providers.create', 'providers.update', 'product_provider_prices.view']);
    $user->assignRole(\Spatie\Permission\Models\Role::findOrCreate('admin', 'web'));
    $primary = 'https://www.fast-cheap.com.ar/categorias/modulo/';
    $battery = 'https://www.fast-cheap.com.ar/categorias/bateria/';
    $flex = 'https://www.fast-cheap.com.ar/categorias/flex/';
    $this->actingAs($user)->get(route('web.providers.create'))->assertOk()->assertSee('URLs adicionales de categorías');
    $this->post(route('web.providers.store'), [
        'business_name' => 'Proveedor categorías', 'tax_id' => 'catalog-multi-urls', 'catalog_url' => $primary,
        'catalog_urls_text' => "  $battery\r\n\n$flex  \n",
    ])->assertRedirect()->assertSessionHasNoErrors();
    $provider = Provider::where('tax_id', 'catalog-multi-urls')->firstOrFail();
    expect($provider->catalog_url)->toBe($primary)->and($provider->catalog_urls)->toBe([$battery, $flex]);
    $this->get(route('web.providers.edit', $provider))->assertOk()->assertSee($battery)->assertSee($flex);
    $this->get(route('web.provider-catalogs.index', ['provider' => $provider->id, 'source_url' => $battery]))
        ->assertOk()->assertSee('URL o categoría')->assertSee('Consultas de las URLs de este proveedor')
        ->assertSee('value="'.$battery.'" selected', false);
});

test('additional URLs have the same security validation and reject duplicates', function (array $extras, ?string $primary) {
    expect(fn () => app(ProviderService::class)->validateProviderData([
        'business_name' => 'Invalid URLs', 'tax_id' => 'invalid-urls', 'catalog_url' => $primary, 'catalog_urls' => $extras,
    ]))->toThrow(ValidationException::class);
})->with([
    'internal IP' => [['http://127.0.0.1/'], null],
    'credentials' => [['https://user:password@public.example.com/'], null],
    'scheme' => [['file:///etc/passwd'], null],
    'duplicates' => [['https://public.example.com/a', 'https://public.example.com/a'], null],
    'repeated primary' => [['https://public.example.com/a'], 'https://public.example.com/a'],
    'too many' => [array_map(fn ($i) => 'https://public.example.com/category/'.$i, range(1, 20)), 'https://public.example.com/'],
]);

test('refreshing one category preserves other category previews and rejects unconfigured URLs', function () {
    config(['queue.default' => 'database']);
    Queue::fake();
    $provider = categoryCatalogProvider();
    $first = categoryCatalogPreview($provider, $provider->catalog_url, 'Módulo');
    $second = categoryCatalogPreview($provider, $provider->catalog_urls[0], 'Batería');
    $token = $second->token;
    $this->actingAs(catalogUser())->post(route('web.providers.catalog.refresh', $provider), ['source_url' => $second->source_url])
        ->assertRedirect(route('web.provider-catalogs.index', ['provider' => $provider->id, 'source_url' => $second->source_url]));
    expect($first->fresh()->products[0]['name'])->toBe('Módulo')->and($second->fresh()->token)->not->toBe($token)
        ->and($second->fresh()->status)->toBe('queued')->and(ProviderCatalog::count())->toBe(2);
    $this->postJson(route('web.providers.catalog.refresh', $provider), ['source_url' => 'https://www.fast-cheap.com.ar/not-configured/'])->assertUnprocessable();
    $this->postJson(route('web.providers.catalog.refresh', $provider), ['source_url' => $second->source_url])->assertUnprocessable();
    Queue::assertPushed(FetchProviderCatalog::class, 1);
});

test('worker reads the selected additional category and never overwrites another category', function () {
    $provider = categoryCatalogProvider();
    $first = categoryCatalogPreview($provider, $provider->catalog_url, 'Módulo');
    $second = categoryCatalogPreview($provider, $provider->catalog_urls[0], 'Batería');
    $second->update(['status' => 'queued', 'products' => [], 'completed_at' => null]);
    $registry = Mockery::mock(CatalogRegistry::class);
    $registry->shouldReceive('fetchPage')->once()->with($second->source_url)
        ->andReturn(['products' => [ProductData::make(['name' => 'Batería nueva'])], 'next_url' => null,
            'current_page' => null, 'total_pages' => null, 'from' => null, 'to' => null, 'total' => null, 'reason' => null]);
    (new FetchProviderCatalog($provider->id, $second->token))->handle($registry);
    expect($second->fresh()->products[0]['name'])->toBe('Batería nueva')->and($first->fresh()->products[0]['name'])->toBe('Módulo');
});

test('all processed category products appear under their supplier with source URLs', function () {
    $provider = categoryCatalogProvider();
    $first = categoryCatalogPreview($provider, $provider->catalog_url, 'Módulo');
    $second = categoryCatalogPreview($provider, $provider->catalog_urls[0], 'Batería');
    $user = catalogUser();
    $this->actingAs($user);
    foreach ([$first, $second] as $preview) {
        $this->post(route('web.providers.catalog.process', $provider), ['token' => $preview->token])->assertRedirect()->assertSessionHasNoErrors();
    }
    expect(ProviderCatalogSnapshot::count())->toBe(2)->and(ProviderCatalogItem::count())->toBe(2);
    $response = $this->getJson(route('web.provider-catalogs.products'))->assertOk()->assertJsonPath('total', 2);
    expect(collect($response->json('options'))->pluck('source_url')->sort()->values()->all())
        ->toBe(collect([$first->source_url, $second->source_url])->sort()->values()->all());
    expect($response->json('optgroups'))->toHaveCount(1)->and($response->json('optgroups.0.label'))->toBe($provider->business_name);
    $this->getJson(route('web.provider-catalogs.products', ['q' => '/categorias/bateria/']))
        ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('options.0.source_url', $second->source_url);
});

test('removing one URL keeps other previews and every processed history', function () {
    $provider = categoryCatalogProvider();
    $first = categoryCatalogPreview($provider, $provider->catalog_url, 'Módulo');
    $second = categoryCatalogPreview($provider, $provider->catalog_urls[0], 'Batería');
    app(CatalogProcessingService::class)->process($provider->id, $second->token, catalogUser()->id);
    app(ProviderService::class)->updateProvider($provider->id, [
        'business_name' => $provider->business_name, 'tax_id' => $provider->tax_id,
        'catalog_urls_text' => $provider->catalog_urls[1],
    ]);
    expect($first->fresh())->not->toBeNull()->and($second->fresh())->toBeNull()
        ->and(ProviderCatalogSnapshot::count())->toBe(1)->and(ProviderCatalogItem::count())->toBe(1);
    expect(fn () => app(ProviderService::class)->updateProvider($provider->id, [
        'business_name' => $provider->business_name, 'tax_id' => $provider->tax_id,
        'catalog_url' => $provider->catalog_urls[1],
    ]))->toThrow(ValidationException::class);
});

test('additional-only configuration works and read-only users cannot consult it', function () {
    config(['queue.default' => 'database']);
    Queue::fake();
    $provider = categoryCatalogProvider();
    $provider->update(['catalog_url' => null]);
    $this->actingAs(catalogUser(['providers.view', 'product_provider_prices.view']))
        ->post(route('web.providers.catalog.refresh', $provider))->assertForbidden();
    $this->actingAs(catalogUser())->post(route('web.providers.catalog.refresh', $provider))->assertRedirect();
    expect(ProviderCatalog::first()->source_url)->toBe($provider->catalog_urls[0]);
    Queue::assertPushed(FetchProviderCatalog::class, 1);
});

test('migration preserves legacy preview contents and rollback refuses to discard category previews', function () {
    $provider = categoryCatalogProvider();
    $first = categoryCatalogPreview($provider, $provider->catalog_url, 'Módulo');
    $migration = require database_path('migrations/2026_10_10_000002_add_multiple_provider_catalog_urls.php');
    $migration->down();
    $migration->up();
    expect($first->fresh()->source_key)->toBe(hash('sha256', $first->source_url))
        ->and($first->fresh()->products[0]['name'])->toBe('Módulo');
    categoryCatalogPreview($provider, 'https://www.fast-cheap.com.ar/categorias/bateria/', 'Batería');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class);
    expect(ProviderCatalog::count())->toBe(2);
});
