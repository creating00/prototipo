<?php

use App\Jobs\FetchProviderCatalog;
use App\Models\Provider;
use App\Models\ProviderCatalog;
use App\Services\Catalogs\CatalogHttpClient;
use App\Services\Catalogs\CatalogRegistry;
use App\Services\Catalogs\I2cSearchAdapter;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

function i2cSearchHtml(array $ids, int $current, int $pages, ?string $next, array $offers = []): string
{
    $html = '<div data-store="search-grid">';
    foreach ($ids as $id) {
        $url = 'https://www.i2cmayorista.com/productos/product-'.$id.'/';
        $html .= '<div data-component="product-list-item"><a class="js-product-item-image-link-private" href="'.$url.'">Igual nombre</a></div>';
        $html .= '<script type="application/ld+json">'.json_encode(['@type' => 'Product', 'name' => 'Igual nombre', 'sku' => 'SKU-'.$id,
            'url' => $url, 'image' => 'https://cdn.example.com/'.$id.'.jpg',
            'offers' => $offers ?: ['@type' => 'Offer', 'price' => '100', 'priceCurrency' => 'ARS']]).'</script>';
    }
    $html .= '<div class="row"><div class="h4"><span>'.$current.'</span><span>/</span><span>'.$pages.'</span></div><div>';
    $html .= $next !== null ? '<a class="swiper-button-next" href="'.htmlspecialchars($next, ENT_QUOTES).'">Siguiente</a>' : '<a class="swiper-button-next disabled">Siguiente</a>';

    return $html.'</div></div></div>';
}

function i2cSearchPreview(int $page = 1): ProviderCatalog
{
    $provider = Provider::create(['business_name' => 'i2C búsqueda', 'tax_id' => (string) Str::uuid(),
        'catalog_url' => 'https://www.i2cmayorista.com/search/page/'.$page.'/?q=modulos&sort_by=price']);

    return ProviderCatalog::create(['provider_id' => $provider->id, 'source_url' => $provider->catalog_url,
        'token' => (string) Str::uuid(), 'products' => [], 'status' => 'queued', 'requested_at' => now()]);
}

function i2cSearchMockPages(array $responses): void
{
    $http = Mockery::mock(CatalogHttpClient::class);
    foreach ($responses as $url => $body) {
        $call = $http->shouldReceive('get')->once()->with($url, ['www.i2cmayorista.com', 'i2cmayorista.com']);
        if ($body instanceof Throwable) {
            $call->andThrow($body);
        } else {
            $call->andReturn(['url' => $url, 'body' => $body]);
        }
    }
    app()->instance(CatalogHttpClient::class, $http);
}

function i2cRepuestosHtml(array $ids, int $current, int $pages, ?string $next): string
{
    // The public theme repeats data-store on its section and inner column.
    return str_replace('<div data-store="search-grid">', '<section class="category-body" data-store="category-grid-26251719"><div data-store="category-grid-26251719">', i2cSearchHtml($ids, $current, $pages, $next)).'</section>';
}

test('i2C Repuestos follows category pages from its root and preserves filters', function () {
    Queue::fake();
    $preview = i2cSearchPreview();
    $url = 'https://www.i2cmayorista.com/repuestos/?sort_by=price';
    $preview->provider->update(['catalog_url' => $url]);
    $preview->update(['source_url' => $url]);
    $next = 'https://www.i2cmayorista.com/repuestos/page/2/?sort_by=price';
    i2cSearchMockPages([
        $url => i2cRepuestosHtml([1, 2], 1, 2, '/repuestos/page/2/?sort_by=price'),
        $next => i2cRepuestosHtml([3], 2, 2, null),
    ]);
    (new FetchProviderCatalog($preview->provider_id, $preview->token))->handle(app(CatalogRegistry::class));
    expect($preview->fresh()->pagination['next_url'])->toBe($next);
    (new FetchProviderCatalog($preview->provider_id, $preview->token, 1))->handle(app(CatalogRegistry::class));
    expect($preview->fresh()->status)->toBe('complete')->and($preview->fresh()->products)->toHaveCount(3);
});

test('i2C Repuestos rejects changes of category search filters or page', function (string $next) {
    $page = app(I2cSearchAdapter::class)->parsePage(i2cRepuestosHtml([1], 1, 2, $next), 'https://www.i2cmayorista.com/repuestos/?sort_by=price');
    expect($page['next_url'])->toBeNull()->and($page['reason'])->not->toBeNull();
})->with([
    '/search/page/2/?sort_by=price',
    '/repuestos/flex/page/2/?sort_by=price',
    '/repuestos/page/2/',
    '/repuestos/page/3/?sort_by=price',
]);

test('i2C Repuestos enforces its page limit and requires a category grid', function () {
    $http = Mockery::mock(CatalogHttpClient::class);
    $http->shouldNotReceive('get');
    app()->instance(CatalogHttpClient::class, $http);
    expect(fn () => app(I2cSearchAdapter::class)->page('https://www.i2cmayorista.com/repuestos/page/401/'))
        ->toThrow(RuntimeException::class, 'páginas de 1 a 400');
    expect(fn () => app(I2cSearchAdapter::class)->parsePage(i2cSearchHtml([1], 1, 1, null), 'https://www.i2cmayorista.com/repuestos/'))
        ->toThrow(RuntimeException::class, 'grilla reconocible');
    expect(fn () => app(I2cSearchAdapter::class)->parsePage(i2cRepuestosHtml([1], 1, 1, null).i2cRepuestosHtml([2], 1, 1, null), 'https://www.i2cmayorista.com/repuestos/'))
        ->toThrow(RuntimeException::class, 'grilla reconocible');
});

test('i2C search follows the published relative next page preserving search and filters', function () {
    Queue::fake();
    $preview = i2cSearchPreview();
    $next = 'https://www.i2cmayorista.com/search/page/2/?q=modulos&sort_by=price';
    i2cSearchMockPages([
        $preview->source_url => i2cSearchHtml([1, 2], 1, 2, '/search/page/2/?q=modulos&sort_by=price'),
        $next => i2cSearchHtml([3], 2, 2, null),
    ]);
    $job = new FetchProviderCatalog($preview->provider_id, $preview->token);
    $job->handle(app(CatalogRegistry::class));
    expect($preview->fresh()->status)->toBe('queued')->and($preview->fresh()->pagination['next_url'])->toBe($next)
        ->and($preview->fresh()->pagination['total'])->toBeNull();
    Queue::assertPushed(FetchProviderCatalog::class, fn ($job) => $job->batch === 1);
    $job->handle(app(CatalogRegistry::class)); // Duplicate delivery cannot refetch.
    (new FetchProviderCatalog($preview->provider_id, $preview->token, 1))->handle(app(CatalogRegistry::class));
    expect($preview->fresh()->status)->toBe('complete')->and($preview->fresh()->products)->toHaveCount(3)
        ->and($preview->fresh()->products[2]['currency'])->toBe('ARS')
        ->and($preview->fresh()->pagination['current_page'])->toBe(2);
    Queue::assertPushed(FetchProviderCatalog::class, 1);
});

test('i2C stops at absolute page 400 and never requests 401', function () {
    Queue::fake();
    $preview = i2cSearchPreview(400);
    i2cSearchMockPages([$preview->source_url => i2cSearchHtml([400], 400, 401, '/search/page/401/?q=modulos&sort_by=price')]);
    (new FetchProviderCatalog($preview->provider_id, $preview->token))->handle(app(CatalogRegistry::class));
    expect($preview->fresh()->status)->toBe('partial')->and($preview->fresh()->message)->toContain('400 páginas')
        ->and($preview->fresh()->products)->toHaveCount(1);
    Queue::assertNothingPushed();
});

test('i2C rejects an initial page over 400 before requesting HTTP', function () {
    $http = Mockery::mock(CatalogHttpClient::class);
    $http->shouldNotReceive('get');
    app()->instance(CatalogHttpClient::class, $http);
    expect(fn () => app(I2cSearchAdapter::class)->page('https://www.i2cmayorista.com/search/page/401/?q=modulos'))
        ->toThrow(RuntimeException::class, 'páginas de 1 a 400');
});

test('i2C never follows unexpected search pagination links', function (string $next) {
    $url = 'https://www.i2cmayorista.com/search/page/1/?q=modulos&sort_by=price';
    $page = app(I2cSearchAdapter::class)->parsePage(i2cSearchHtml([1], 1, 2, $next), $url);
    expect($page['next_url'])->toBeNull()->and($page['reason'])->not->toBeNull();
})->with([
    'private address' => 'http://127.0.0.1/search/page/2/?q=modulos&sort_by=price',
    'other domain' => '//evil.example/search/page/2/?q=modulos&sort_by=price',
    'changed search' => '/search/page/2/?q=baterias&sort_by=price',
    'dropped filters' => '/search/page/2/?q=modulos',
    'skipped page' => '/search/page/3/?q=modulos&sort_by=price',
    'repeated page' => '/search/page/1/?q=modulos&sort_by=price',
    'other category' => '/repuestos/page/2/?q=modulos&sort_by=price',
]);

test('i2C preserves earlier products on page failures changed totals or overlapping products', function (mixed $last) {
    Queue::fake();
    $preview = i2cSearchPreview();
    $next = 'https://www.i2cmayorista.com/search/page/2/?q=modulos&sort_by=price';
    i2cSearchMockPages([$preview->source_url => i2cSearchHtml([1], 1, 2, $next), $next => $last]);
    (new FetchProviderCatalog($preview->provider_id, $preview->token))->handle(app(CatalogRegistry::class));
    (new FetchProviderCatalog($preview->provider_id, $preview->token, 1))->handle(app(CatalogRegistry::class));
    expect($preview->fresh()->status)->toBe('partial')->and($preview->fresh()->products[0]['code'])->toBe('SKU-1');
})->with([
    'blocked response' => fn () => new RuntimeException('HTTP 403'),
    'changed total' => fn () => i2cSearchHtml([2], 2, 3, '/search/page/3/?q=modulos&sort_by=price'),
    'overlap' => fn () => i2cSearchHtml([1, 2], 2, 2, null),
]);

test('i2C partial metadata or an intermediate starting page never claims the complete search', function () {
    Queue::fake();
    $preview = i2cSearchPreview(2);
    i2cSearchMockPages([$preview->source_url => i2cSearchHtml([2], 2, 2, null)]);
    (new FetchProviderCatalog($preview->provider_id, $preview->token))->handle(app(CatalogRegistry::class));
    expect($preview->fresh()->status)->toBe('partial');
    $html = i2cSearchHtml([1], 1, 1, null);
    $page = app(I2cSearchAdapter::class)->parsePage(str_replace('<span>1</span><span>/</span><span>1</span>', '', $html), 'https://www.i2cmayorista.com/search/?q=modulos');
    expect($page['reason'])->not->toBeNull();
});

test('i2C search ignores products outside its grid and detects cards missing JSON LD', function () {
    $html = i2cSearchHtml([1], 1, 1, null);
    $html .= '<script type="application/ld+json">'.json_encode(['@type' => 'Product', 'name' => 'Unrelated', 'url' => 'https://www.i2cmayorista.com/productos/related/']).'</script>';
    $url = 'https://www.i2cmayorista.com/search/?q=modulos';
    $page = app(I2cSearchAdapter::class)->parsePage($html, $url);
    expect($page['products'])->toHaveCount(1)->and($page['reason'])->toBeNull();
    $html = str_replace('<div class="row">', '<div data-component="product-list-item"><a class="js-product-item-image-link-private" href="https://www.i2cmayorista.com/productos/missing/">Missing</a></div><div class="row">', $html);
    expect(app(I2cSearchAdapter::class)->parsePage($html, $url)['reason'])->toContain('todos los productos');
});

test('i2C preserves distinct published offers when a product has more than one price', function () {
    Queue::fake();
    $preview = i2cSearchPreview();
    $offers = [['@type' => 'Offer', 'price' => '100', 'priceCurrency' => 'ARS'], ['@type' => 'Offer', 'price' => '200', 'priceCurrency' => 'ARS']];
    i2cSearchMockPages([$preview->source_url => i2cSearchHtml([1], 1, 1, null, $offers)]);
    (new FetchProviderCatalog($preview->provider_id, $preview->token))->handle(app(CatalogRegistry::class));
    expect($preview->fresh()->products)->toHaveCount(2)->and(array_column($preview->fresh()->products, 'price'))->toBe(['100', '200']);
});

test('numbered pagination is enabled only for verified i2C listing URLs', function () {
    expect(CatalogRegistry::paginatedAdapter('https://www.i2cmayorista.com/search/page/1/?q=modulos'))->toBe(I2cSearchAdapter::class)
        ->and(CatalogRegistry::paginatedAdapter('https://www.i2cmayorista.com/'))->toBeNull()
        ->and(CatalogRegistry::paginatedAdapter('https://www.i2cmayorista.com/repuestos/page/1/'))->toBe(I2cSearchAdapter::class)
        ->and(CatalogRegistry::paginatedAdapter('https://www.i2cmayorista.com/repuestos/'))->toBe(I2cSearchAdapter::class)
        ->and(CatalogRegistry::paginatedAdapter('https://www.i2cmayorista.com/otra-categoria/page/1/'))->toBeNull()
        ->and(CatalogRegistry::paginatedAdapter('https://unvarg.com/search/?q=lenovo'))->toBeNull();
});
