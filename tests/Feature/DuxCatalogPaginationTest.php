<?php

use App\Jobs\FetchProviderCatalog;
use App\Models\Provider;
use App\Models\ProviderCatalog;
use App\Services\Catalogs\CatalogHttpClient;
use App\Services\Catalogs\CatalogRegistry;
use App\Services\Catalogs\DuxAdapter;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

function duxPagedHtml(array $codes, int $current, ?string $next): string
{
    $rows = array_map(fn ($code) => ['product' => ['item' => 'Modulo '.$code, 'sku' => $code, 'total' => 100, 'moneda' => 'PESOS']], $codes);
    $html = str_repeat('<div data-testid="card-product-info">Placeholder</div>', count($codes));
    $html .= '<script>self.__next_f.push('.json_encode([1, 'a:'.json_encode($rows)."\n"]).')</script>';
    $html .= '<div class="inline-flex"><div><div class="z-10">'.$current.'</div></div>';
    $html .= $next !== null ? '<a href="'.htmlspecialchars($next, ENT_QUOTES).'"><i class="pi pi-angle-right"></i></a>'
        : '<div class="pointer-events-none"><i class="pi pi-angle-right"></i></div>';

    return $html.'</div>';
}

function duxPagedPreview(int $page = 1): ProviderCatalog
{
    $provider = Provider::create(['business_name' => 'Dux', 'tax_id' => (string) Str::uuid(),
        'catalog_url' => 'https://catalogo.duxsoftware.com.ar/byteriojauno?id_rubro=128087&page='.$page]);

    return ProviderCatalog::create(['provider_id' => $provider->id, 'source_url' => $provider->catalog_url,
        'token' => (string) Str::uuid(), 'status' => 'queued', 'products' => [], 'requested_at' => now()]);
}

function duxPagedMock(array $pages): void
{
    $http = Mockery::mock(CatalogHttpClient::class);
    foreach ($pages as $url => $body) {
        $call = $http->shouldReceive('get')->once()->with($url, ['catalogo.duxsoftware.com.ar']);
        if ($body instanceof Throwable) {
            $call->andThrow($body);
        } else {
            $call->andReturn(['url' => $url, 'body' => $body]);
        }
    }
    app()->instance(CatalogHttpClient::class, $http);
}

test('Dux queue continues past four pages retaining category filters and verifies the disabled final control', function () {
    Queue::fake();
    $preview = duxPagedPreview();
    $pages = [];
    for ($i = 1; $i <= 6; $i++) {
        $url = 'https://catalogo.duxsoftware.com.ar/byteriojauno?id_rubro=128087&page='.$i;
        $pages[$url] = duxPagedHtml(['SKU-'.$i], $i, $i === 6 ? null : '/byteriojauno?id_rubro=128087&page='.($i + 1));
    }
    duxPagedMock($pages);
    for ($batch = 0; $batch < 6; $batch++) {
        (new FetchProviderCatalog($preview->provider_id, $preview->token, $batch))->handle(app(CatalogRegistry::class));
        expect($preview->fresh()->status)->toBe($batch === 5 ? 'complete' : 'queued');
    }
    expect($preview->fresh()->products)->toHaveCount(6)->and($preview->fresh()->pagination['pages_read'])->toBe(6)
        ->and($preview->fresh()->pagination['total'])->toBeNull()->and($preview->fresh()->products[5]['currency'])->toBe('PESOS');
    Queue::assertPushed(FetchProviderCatalog::class, 5);
});

test('Dux refuses unsafe or changed pagination destinations', function (string $next) {
    $url = 'https://catalogo.duxsoftware.com.ar/byteriojauno?id_rubro=128087&page=1';
    $page = app(DuxAdapter::class)->parsePage(duxPagedHtml(['A'], 1, $next), $url);
    expect($page['next_url'])->toBeNull()->and($page['reason'])->not->toBeNull();
})->with([
    'private' => 'http://127.0.0.1/?page=2',
    'other catalog' => '/otro?id_rubro=128087&page=2',
    'changed category' => '/byteriojauno?id_rubro=9&page=2',
    'dropped category' => '/byteriojauno?page=2',
    'changed search' => '/byteriojauno?id_rubro=128087&productSearch=other&page=2',
    'skipped page' => '/byteriojauno?id_rubro=128087&page=3',
    'credentials' => 'https://user:secret@catalogo.duxsoftware.com.ar/byteriojauno?id_rubro=128087&page=2',
]);

test('Dux stops at page 400 and rejects an initial page above the limit before HTTP', function () {
    Queue::fake();
    $preview = duxPagedPreview(400);
    duxPagedMock([$preview->source_url => duxPagedHtml(['A'], 400, '/byteriojauno?id_rubro=128087&page=401')]);
    (new FetchProviderCatalog($preview->provider_id, $preview->token))->handle(app(CatalogRegistry::class));
    expect($preview->fresh()->status)->toBe('partial')->and($preview->fresh()->message)->toContain('400 páginas');
    Queue::assertNothingPushed();
    expect(fn () => app(DuxAdapter::class)->page('https://catalogo.duxsoftware.com.ar/byteriojauno?id_rubro=128087&page=401'))
        ->toThrow(RuntimeException::class, 'páginas de 1 a 400');
});

test('Dux preserves prior products after failed pages or repeated SKUs across different page URLs', function (mixed $last) {
    Queue::fake();
    $preview = duxPagedPreview();
    $next = 'https://catalogo.duxsoftware.com.ar/byteriojauno?id_rubro=128087&page=2';
    duxPagedMock([$preview->source_url => duxPagedHtml(['A'], 1, $next), $next => $last]);
    (new FetchProviderCatalog($preview->provider_id, $preview->token))->handle(app(CatalogRegistry::class));
    (new FetchProviderCatalog($preview->provider_id, $preview->token, 1))->handle(app(CatalogRegistry::class));
    expect($preview->fresh()->status)->toBe('partial')->and($preview->fresh()->products)->toHaveCount(1)
        ->and($preview->fresh()->products[0]['code'])->toBe('A');
})->with([
    'HTTP failure' => fn () => new RuntimeException('HTTP 403'),
    'changed format' => 'Placeholder',
    'repeated SKU' => fn () => duxPagedHtml(['A'], 2, null),
]);

test('Dux cannot claim completeness without the published page and final signal or when starting midway', function () {
    Queue::fake();
    $preview = duxPagedPreview(2);
    duxPagedMock([$preview->source_url => duxPagedHtml(['A'], 2, null)]);
    (new FetchProviderCatalog($preview->provider_id, $preview->token))->handle(app(CatalogRegistry::class));
    expect($preview->fresh()->status)->toBe('partial');
    $url = 'https://catalogo.duxsoftware.com.ar/byteriojauno?id_rubro=128087&page=1';
    $html = duxPagedHtml(['A'], 1, null);
    foreach ([str_replace('pointer-events-none', '', $html), str_replace('class="z-10"', 'class="unknown"', $html), str_replace('>1</div>', '>2</div>', $html)] as $invalid) {
        expect(app(DuxAdapter::class)->parsePage($invalid, $url)['reason'])->not->toBeNull();
    }
    $html .= '<div data-testid="card-product-info">Missing JSON</div>';
    expect(app(DuxAdapter::class)->parsePage($html, $url)['reason'])->toContain('todos los productos');
});
