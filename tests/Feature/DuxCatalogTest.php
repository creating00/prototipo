<?php

use App\Services\Catalogs\CatalogHttpClient;
use App\Services\Catalogs\CatalogRegistry;
use App\Services\Catalogs\DuxAdapter;

function duxFlightFixture(array $rows, ?string $next = null, bool $split = false): string
{
    $record = '4a:'.json_encode(['children' => array_map(fn ($row) => ['$', '$L4a', $row['sku'], ['product' => $row]], $rows)])."\n";
    $chunks = $split ? str_split($record, 30) : [$record];
    $html = '<div data-testid="card-product-info">Placeholder</div><script>self.__next_f.push([0])</script>';
    foreach ($chunks as $chunk) {
        $html .= '<script>self.__next_f.push('.json_encode([1, $chunk]).')</script>';
    }

    return $html.($next ? '<a href="'.htmlspecialchars($next, ENT_QUOTES).'">Siguiente</a>' : '');
}

test('Dux reads structured props split across scripts without evaluating JavaScript', function () {
    $row = ['item' => 'Cable "USB" <azul>', 'sku' => 'ABC', 'total' => 0, 'moneda' => 'PESOS', 'stock_disponible' => 0, 'descripcion' => null, 'url' => 'https://erp.duxsoftware.com.ar/image.jpg', 'cantidad_variantes' => 2];
    $products = app(DuxAdapter::class)->parse(duxFlightFixture([$row, $row], split: true), 'https://catalogo.duxsoftware.com.ar/byteriojauno?page=1');
    expect($products)->toHaveCount(1)->and($products[0]['price'])->toBe('0')
        ->and($products[0]['currency'])->toBe('PESOS')->and($products[0]['availability'])->toBe('Stock publicado: 0')
        ->and($products[0]['model'])->toBeNull()->and($products[0]['brand'])->toBeNull()
        ->and($products[0]['url'])->toBe('https://catalogo.duxsoftware.com.ar/byteriojauno?page=1')
        ->and($products[0]['details']['cantidad_variantes'])->toBe(2);
    expect(app(DuxAdapter::class)->parse('<script>self.__next_f.push(alert(1))</script>', 'https://catalogo.duxsoftware.com.ar/byteriojauno'))->toBe([]);
});

test('Dux preserves absent price and currency instead of using untaxed price as fallback', function () {
    $rows = app(DuxAdapter::class)->parse(duxFlightFixture([['item' => 'Item', 'sku' => 'ABC', 'total_sin_impuestos' => 100]]), 'https://catalogo.duxsoftware.com.ar/byteriojauno');
    expect($rows[0]['price'])->toBeNull()->and($rows[0]['currency'])->toBeNull()->and($rows[0]['availability'])->toBeNull();
});

test('Dux pagination only follows published next links within the same catalog and search', function () {
    $url = 'https://catalogo.duxsoftware.com.ar/byteriojauno?productSearch=cable&page=1';
    $adapter = app(DuxAdapter::class);
    expect($adapter->nextPage(duxFlightFixture([], '/byteriojauno?productSearch=cable&page=2'), $url))
        ->toBe('https://catalogo.duxsoftware.com.ar/byteriojauno?productSearch=cable&page=2');
    foreach (['http://127.0.0.1/?page=2', 'https://other.example.com/byteriojauno?productSearch=cable&page=2', '/otro?productSearch=cable&page=2', '/byteriojauno?productSearch=other&page=2', '/byteriojauno?productSearch=cable&page=9'] as $next) {
        expect($adapter->nextPage(duxFlightFixture([], $next), $url))->toBeNull();
    }
});

test('Dux registry uses its adapter and stops after four published pages', function () {
    $http = Mockery::mock(CatalogHttpClient::class);
    for ($page = 1; $page <= 4; $page++) {
        $url = 'https://catalogo.duxsoftware.com.ar/byteriojauno?productSearch=&page='.$page;
        $http->shouldReceive('get')->once()->with($url, ['catalogo.duxsoftware.com.ar'])->andReturn([
            'body' => duxFlightFixture([['item' => 'Item '.$page, 'sku' => (string) $page, 'total' => 100]], '/byteriojauno?productSearch=&page='.($page + 1)), 'url' => $url,
        ]);
    }
    $this->app->instance(CatalogHttpClient::class, $http);
    $result = app(CatalogRegistry::class)->fetch('https://catalogo.duxsoftware.com.ar/byteriojauno?productSearch=&page=1');
    expect($result->status)->toBe('partial')->and($result->products)->toHaveCount(4);
});

test('Dux keeps earlier products if a subsequent page cannot be extracted', function () {
    $url = 'https://catalogo.duxsoftware.com.ar/byteriojauno?page=1';
    $next = 'https://catalogo.duxsoftware.com.ar/byteriojauno?page=2';
    $http = Mockery::mock(CatalogHttpClient::class);
    $http->shouldReceive('get')->once()->with($url, ['catalogo.duxsoftware.com.ar'])->andReturn([
        'url' => $url, 'body' => duxFlightFixture([['item' => 'Item', 'sku' => 'ABC']], '/byteriojauno?page=2'),
    ]);
    $http->shouldReceive('get')->once()->with($next, ['catalogo.duxsoftware.com.ar'])->andReturn(['url' => $next, 'body' => 'Changed format']);
    $result = (new DuxAdapter($http))->fetch($url);
    expect($result->status)->toBe('partial')->and($result->products)->toHaveCount(1)->and($result->message)->toContain('falló una página');
});

test('Dux reports extraction failure when the first page has no readable data', function () {
    $http = Mockery::mock(CatalogHttpClient::class);
    $http->shouldReceive('get')->once()->andReturn(['url' => 'https://catalogo.duxsoftware.com.ar/byteriojauno', 'body' => '<div>Placeholder</div>']);
    expect(fn () => (new DuxAdapter($http))->fetch('https://catalogo.duxsoftware.com.ar/byteriojauno'))->toThrow(RuntimeException::class);
});
