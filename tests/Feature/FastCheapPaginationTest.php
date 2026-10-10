<?php

use App\Jobs\FetchProviderCatalog;
use App\Models\Provider;
use App\Models\ProviderCatalog;
use App\Models\User;
use App\Services\Catalogs\CatalogHttpClient;
use App\Services\Catalogs\CatalogProcessingService;
use App\Services\Catalogs\CatalogRegistry;
use App\Services\Catalogs\FastCheapAdapter;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

function fastCheapUser(): User
{
    $user = User::factory()->create();
    $permissions = ['providers.view', 'providers.update', 'product_provider_prices.view'];
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $user->givePermissionTo($permissions);

    return $user;
}

function fastCheapHtml(array $ids, string $range, ?int $page = null, ?string $next = null, int $pages = 2): string
{
    $html = '<p class="woocommerce-result-count">'.$range.'</p>';
    foreach ($ids as $id) {
        $html .= '<div class="product type-product instock"><h3 class="product-title"><a href="https://www.fast-cheap.com.ar/producto/'.$id.'/">Same name</a></h3><div class="product-image"><img src="https://www.fast-cheap.com.ar/images/'.$id.'.jpg"></div><span class="price"><span class="woocommerce-Price-amount">$15,000.00</span></span></div>';
    }
    if ($page) {
        $html .= '<div data-post_type="product" data-cur_page="'.$page.'" data-total_page="'.$pages.'">'
            .($next ? '<a href="'.$next.'">Cargar mas productos</a>' : '').'</div>';
    }

    return $html;
}

function fastCheapPreview(): ProviderCatalog
{
    $provider = Provider::create(['business_name' => 'Fast Cheap', 'tax_id' => (string) Str::uuid(), 'catalog_url' => 'https://www.fast-cheap.com.ar/categorias/modulo/']);

    return ProviderCatalog::create(['provider_id' => $provider->id, 'source_url' => $provider->catalog_url,
        'token' => (string) Str::uuid(), 'status' => 'queued', 'products' => [], 'requested_at' => now(), 'completed_at' => null]);
}

function fastCheapMockPages(array $responses): void
{
    $http = Mockery::mock(CatalogHttpClient::class);
    foreach ($responses as $url => $response) {
        $call = $http->shouldReceive('get')->once()->with($url, ['www.fast-cheap.com.ar', 'fast-cheap.com.ar']);
        if ($response instanceof Throwable) {
            $call->andThrow($response);
        } else {
            $call->andReturn(['url' => $url, 'body' => $response]);
        }
    }
    app()->instance(CatalogHttpClient::class, $http);
}

test('Fast Cheap follows public load more across jobs and can archive the complete category', function () {
    Queue::fake();
    $catalog = fastCheapPreview();
    $next = $catalog->source_url.'page/2/';
    fastCheapMockPages([
        $catalog->source_url => fastCheapHtml([1, 2], 'Showing 1–2 Products of 3 Products', 1, $next),
        $next => fastCheapHtml([3], 'Showing 3–3 Products of 3 Products'),
    ]);
    $job = new FetchProviderCatalog($catalog->provider_id, $catalog->token);
    $job->handle(app(CatalogRegistry::class));
    expect($catalog->fresh()->status)->toBe('queued')->and($catalog->fresh()->products)->toHaveCount(2);
    Queue::assertPushed(FetchProviderCatalog::class, fn ($job) => $job->batch === 1 && $job->token === $catalog->token);
    // Duplicate delivery does not perform HTTP again or append another continuation.
    $job->handle(app(CatalogRegistry::class));
    Queue::assertPushed(FetchProviderCatalog::class, 1);
    (new FetchProviderCatalog($catalog->provider_id, $catalog->token, 1))->handle(app(CatalogRegistry::class));
    $catalog->refresh();
    expect($catalog->status)->toBe('complete')->and($catalog->products)->toHaveCount(3)
        ->and($catalog->pagination['pages_read'])->toBe(2)->and($catalog->completed_at)->not->toBeNull()
        ->and($catalog->products[2]['image'])->toEndWith('/3.jpg')->and($catalog->products[2]['currency'])->toBeNull();
    $snapshot = app(CatalogProcessingService::class)->process($catalog->provider_id, $catalog->token, fastCheapUser()->id);
    expect($snapshot->product_count)->toBe(3)->and($snapshot->products()->count())->toBe(3);
});

test('a later Fast Cheap page failure preserves products as an explicitly partial listing', function () {
    Queue::fake();
    $catalog = fastCheapPreview();
    $next = $catalog->source_url.'page/2/';
    fastCheapMockPages([$catalog->source_url => fastCheapHtml([1], 'Showing 1–1 Products of 2 Products', 1, $next), $next => new RuntimeException('HTTP 403')]);
    (new FetchProviderCatalog($catalog->provider_id, $catalog->token))->handle(app(CatalogRegistry::class));
    (new FetchProviderCatalog($catalog->provider_id, $catalog->token, 1))->handle(app(CatalogRegistry::class));
    expect($catalog->fresh()->status)->toBe('partial')->and($catalog->fresh()->products)->toHaveCount(1)
        ->and($catalog->fresh()->message)->toContain('HTTP 403');
});

test('Fast Cheap verifies single page categories and rejects unexpected load more links', function () {
    $adapter = app(FastCheapAdapter::class);
    $url = 'https://www.fast-cheap.com.ar/categorias/modulo/';
    $page = $adapter->parsePage(fastCheapHtml([1, 2], 'Showing all 2 Products'), $url);
    expect($page['total'])->toBe(2)->and($page['from'])->toBe(1)->and($page['to'])->toBe(2);
    foreach (['http://127.0.0.1/page/2/', 'https://evil.example/page/2/', $url.'page/1/', 'https://www.fast-cheap.com.ar/categorias/flex/page/2/', $url.'page/2/?filter=changed'] as $next) {
        $page = $adapter->parsePage(fastCheapHtml([1], 'Showing 1–1 Products of 2 Products', 1, $next), $url);
        expect($page['next_url'])->toBeNull()->and($page['reason'])->not->toBeNull();
    }
});

test('changed totals repeated pages and missing products never become complete', function (array $ids, string $range) {
    Queue::fake();
    $catalog = fastCheapPreview();
    $next = $catalog->source_url.'page/2/';
    fastCheapMockPages([$catalog->source_url => fastCheapHtml([1], 'Showing 1–1 Products of 2 Products', 1, $next), $next => fastCheapHtml($ids, $range)]);
    (new FetchProviderCatalog($catalog->provider_id, $catalog->token))->handle(app(CatalogRegistry::class));
    (new FetchProviderCatalog($catalog->provider_id, $catalog->token, 1))->handle(app(CatalogRegistry::class));
    expect($catalog->fresh()->status)->toBe('partial');
})->with([
    'total changed' => [[2], 'Showing 2–2 Products of 3 Products'],
    'repeated product' => [[1], 'Showing 2–2 Products of 2 Products'],
    'skipped range' => [[2], 'Showing 3–3 Products of 2 Products'],
]);

test('ongoing paginated queries expire on lack of activity rather than their original request date', function () {
    $catalog = fastCheapPreview();
    $catalog->update(['requested_at' => now()->subMinutes(10), 'last_activity_at' => now(), 'products' => [['name' => 'Published']]]);
    $this->actingAs(fastCheapUser())->get(route('web.provider-catalogs.index'))->assertOk();
    expect($catalog->fresh()->status)->toBe('queued');
    $catalog->update(['last_activity_at' => now()->subMinutes(3)]);
    $this->get(route('web.provider-catalogs.index'))->assertOk();
    expect($catalog->fresh()->status)->toBe('partial')->and($catalog->fresh()->products)->toHaveCount(1);
});

test('Fast Cheap bounds continuation to 400 pages', function () {
    Queue::fake();
    $catalog = fastCheapPreview();
    $url = $catalog->source_url.'page/400/';
    $catalog->update(['pagination' => ['pages_read' => 399, 'next_url' => $url, 'from' => 1, 'to' => 399, 'total' => 401, 'total_pages' => 401]]);
    fastCheapMockPages([$url => fastCheapHtml([400], 'Showing 400–400 Products of 401 Products', 400, $catalog->source_url.'page/401/', 401)]);
    (new FetchProviderCatalog($catalog->provider_id, $catalog->token, 399))->handle(app(CatalogRegistry::class));
    expect($catalog->fresh()->status)->toBe('partial')->and($catalog->fresh()->message)->toContain('400 páginas');
    Queue::assertNothingPushed();
});

test('a continuation dispatch failure retains the first page and closes the query', function () {
    $catalog = fastCheapPreview();
    fastCheapMockPages([$catalog->source_url => fastCheapHtml([1], 'Showing 1–1 Products of 2 Products', 1, $catalog->source_url.'page/2/')]);
    $this->mock(\Illuminate\Contracts\Bus\Dispatcher::class, fn ($mock) => $mock->shouldReceive('dispatch')->once()->andThrow(new RuntimeException('Queue unavailable')));
    (new FetchProviderCatalog($catalog->provider_id, $catalog->token))->handle(app(CatalogRegistry::class));
    expect($catalog->fresh()->status)->toBe('partial')->and($catalog->fresh()->products)->toHaveCount(1)
        ->and($catalog->fresh()->message)->toContain('siguiente página a la cola');
});

test('failure notification for an older batch cannot interrupt its queued continuation', function () {
    Queue::fake();
    $catalog = fastCheapPreview();
    fastCheapMockPages([$catalog->source_url => fastCheapHtml([1], 'Showing 1–1 Products of 2 Products', 1, $catalog->source_url.'page/2/')]);
    $job = new FetchProviderCatalog($catalog->provider_id, $catalog->token);
    $job->handle(app(CatalogRegistry::class));
    $job->failed(new RuntimeException('Old delivery failure'));
    expect($catalog->fresh()->status)->toBe('queued')->and($catalog->fresh()->pagination['pages_read'])->toBe(1);
});

test('catalog jobs queued before the pagination deployment retain the first batch default', function () {
    $job = new FetchProviderCatalog(1, (string) Str::uuid());
    unset($job->batch);
    $restored = unserialize(serialize($job));
    expect($restored->batch)->toBe(0);
});

test('the database worker executes the persisted continuation payload', function () {
    config(['queue.default' => 'database']);
    $catalog = fastCheapPreview();
    $next = $catalog->source_url.'page/2/';
    fastCheapMockPages([
        $catalog->source_url => fastCheapHtml([1], 'Showing 1–1 Products of 2 Products', 1, $next),
        $next => fastCheapHtml([2], 'Showing 2–2 Products of 2 Products'),
    ]);
    (new FetchProviderCatalog($catalog->provider_id, $catalog->token))->handle(app(CatalogRegistry::class));
    expect(\Illuminate\Support\Facades\DB::table('jobs')->count())->toBe(1);
    $queued = Queue::connection('database')->pop();
    expect($queued)->not->toBeNull();
    $queued->fire();
    $queued->delete();
    expect($catalog->fresh()->status)->toBe('complete')->and($catalog->fresh()->products)->toHaveCount(2);
});

test('catalog progress renders friendly text without exposing Blade instructions', function (?int $total) {
    $catalog = fastCheapPreview();
    $catalog->update(['products' => array_fill(0, 6, \App\Services\Catalogs\ProductData::make(['name' => 'Publicado'])),
        'pagination' => ['pages_read' => 1, 'total' => $total], 'last_activity_at' => now(), 'message' => 'Detalle técnico de cola']);
    $response = $this->actingAs(fastCheapUser())->get(route('web.provider-catalogs.index'));
    $response->assertOk()->assertDontSee('@if', false)->assertDontSee('@endif', false)->assertDontSee('Detalle técnico de cola');
    $xpath = \App\Services\Catalogs\ProductData::dom($response->getContent());
    $text = preg_replace('/\s+/u', ' ', trim($xpath->query('//*[@data-catalog-progress-label]')->item(0)->textContent));
    expect($text)->toBe($total ? 'Leyendo el catálogo… Encontramos 6 de 849 productos.' : 'Leyendo el catálogo… Encontramos 6 productos.');
})->with(['published total' => [849], 'unknown total' => [null]]);

test('published products show five per page inside a collapsible list and keep the category in links', function () {
    $catalog = fastCheapPreview();
    $products = [];
    for ($number = 1; $number <= 12; $number++) {
        $products[] = \App\Services\Catalogs\ProductData::make(['name' => 'Producto de prueba '.$number]);
    }
    $catalog->update(['products' => $products, 'status' => 'complete', 'completed_at' => now()]);
    $params = ['provider' => $catalog->provider_id, 'source_url' => $catalog->source_url];
    $response = $this->actingAs(fastCheapUser())->get(route('web.provider-catalogs.index', $params));
    $response->assertOk()->assertViewHas('entries', fn ($entries) => $entries->perPage() === 5 && $entries->count() === 5 && $entries->total() === 12)
        ->assertSee('Mostrando 1 a 5 de 12 productos.')->assertDontSee('Producto de prueba 6');
    $xpath = \App\Services\Catalogs\ProductData::dom($response->getContent());
    expect($xpath->query('//details[@id="catalog-published-products"]/summary')->length)->toBe(1);
    $next = $response->viewData('entries')->nextPageUrl();
    expect($next)->toContain('source_url='.urlencode($catalog->source_url))->toEndWith('#catalog-published-products');
    $this->get($next)->assertOk()->assertSee('Mostrando 6 a 10 de 12 productos.')
        ->assertSee('Producto de prueba 6')->assertDontSee('Producto de prueba 11');
});
