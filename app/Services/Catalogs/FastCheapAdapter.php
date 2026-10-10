<?php

namespace App\Services\Catalogs;

use RuntimeException;

/** Verified Kapee/WooCommerce public listing. The blocked Store API is never queried. */
class FastCheapAdapter implements CatalogAdapter, PaginatedCatalogAdapter
{
    public function __construct(private CatalogHttpClient $http) {}

    public function parse(string $html): array
    {
        $xpath = ProductData::dom($html);
        $products = [];
        $class = fn (string $name) => 'contains(concat(" ", normalize-space(@class), " "), " '.$name.' ")';
        foreach ($xpath->query('//*['.$class('product').' and '.$class('type-product').']') as $card) {
            $title = $xpath->query('.//*['.$class('product-title').']/a', $card)->item(0);
            if (! $title) {
                continue;
            }
            $priceNode = $xpath->query('.//*['.$class('price').']', $card)->item(0);
            $amountNodes = $priceNode ? $xpath->query('.//*['.$class('woocommerce-Price-amount').']', $priceNode) : null;
            // Sales have del/ins amounts; ranges have several amounts and cannot be a single price.
            $sale = $priceNode ? $xpath->query('.//ins//*['.$class('woocommerce-Price-amount').']', $priceNode)->item(0) : null;
            $amount = $sale ?? ($amountNodes?->length === 1 ? $amountNodes->item(0) : null);
            $price = $amount ? preg_replace('/[^0-9.,]/', '', $amount->textContent) : null;
            $price = $price && preg_match('/^\d+(?:,\d{3})*(?:\.\d+)?$/D', $price) ? str_replace(',', '', $price) : null;
            $code = $xpath->query('.//*[@data-product_sku]', $card)->item(0)?->getAttribute('data-product_sku');
            $image = $xpath->query('.//*['.$class('product-image').']//img[@src]', $card)->item(0)?->getAttribute('src');
            $classes = explode(' ', $card->getAttribute('class'));
            $availability = in_array('outofstock', $classes, true) ? 'outofstock'
                : (in_array('instock', $classes, true) ? 'instock' : null);
            $product = ProductData::make([
                'name' => $title->textContent, 'code' => $code, 'price' => $price,
                'availability' => $availability, 'image' => $image, 'url' => $title->getAttribute('href'),
                'details' => ['tarjeta_publicada' => ProductData::text($card->textContent), 'precio_publicado' => ProductData::text($priceNode?->textContent), 'clases_publicadas' => $classes],
            ]);
            // Keep repeated cards only once, without merging different products by name.
            $products[hash('sha256', json_encode($product))] = $product;
        }

        return array_values($products);
    }

    public function fetch(string $url): CatalogResult
    {
        $response = $this->http->get($url, ['www.fast-cheap.com.ar', 'fast-cheap.com.ar']);
        $products = $this->parse($response['body']);
        if ($products === []) {
            throw new RuntimeException('Fast Cheap no publicó tarjetas reconocibles. Cambió el formato o la página requiere otra integración.');
        }

        return new CatalogResult(array_slice($products, 0, 500), 'partial', 'Solo tarjetas publicadas en la página indicada; sin recorrer paginación ni fichas individuales. La API respondió 403 durante la evaluación y no se utiliza. El símbolo $ no permite inferir moneda. Rangos de variantes quedan sin un precio único.');
    }

    /** One public load-more page per queue job; no API, JavaScript or images are downloaded. */
    public function page(string $url): array
    {
        $response = $this->http->get($url, ['www.fast-cheap.com.ar', 'fast-cheap.com.ar']);
        if (! $this->samePage($url, $response['url'])) {
            throw new RuntimeException('Fast Cheap redirigió fuera de la página solicitada.');
        }

        return $this->parsePage($response['body'], $response['url']);
    }

    public function parsePage(string $html, string $url): array
    {
        $xpath = ProductData::dom($html);
        $products = $this->parse($html);
        if ($products === []) {
            throw new RuntimeException('Fast Cheap no publicó tarjetas reconocibles en esta página.');
        }
        $pager = $xpath->query('//*[@data-post_type="product" and @data-cur_page and @data-total_page]')->item(0);
        $count = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " woocommerce-result-count ")]')->item(0);
        $range = [];
        preg_match('/Showing\s+(\d+)\s*[–-]\s*(\d+)\s+Products\s+of\s+(\d+)\s+Products/iu', $count?->textContent ?? '', $range);
        if (! $range && preg_match('/Showing all\s+(\d+)\s+Products/iu', $count?->textContent ?? '', $all)) {
            $range = [null, 1, (int) $all[1], (int) $all[1]];
        }
        $current = $pager ? (int) $pager->getAttribute('data-cur_page') : null;
        $pages = $pager ? (int) $pager->getAttribute('data-total_page') : null;
        $next = $pager ? $xpath->query('.//a[@href]', $pager)->item(0)?->getAttribute('href') : null;
        $reason = null;
        if ($next && (! $current || ! $pages || ! $this->validNext($url, $next, $current))) {
            $reason = 'El enlace de cargar más no corresponde a la siguiente página de esta categoría.';
            $next = null;
        }
        if ($current && $pages && $current < $pages && ! $next) {
            $reason ??= 'Falta el enlace publicado para continuar la categoría.';
        }

        return [
            'products' => $products, 'next_url' => $next ?: null,
            'current_page' => $current, 'total_pages' => $pages,
            'from' => isset($range[1]) ? (int) $range[1] : null,
            'to' => isset($range[2]) ? (int) $range[2] : null,
            'total' => isset($range[3]) ? (int) $range[3] : null,
            'reason' => $reason,
        ];
    }

    private function samePage(string $left, string $right): bool
    {
        $a = app(PublicUrl::class)->parts($left);
        $b = app(PublicUrl::class)->parts($right);

        return $a['scheme'] === $b['scheme']
            && strtolower($a['host']) === strtolower($b['host'])
            && rtrim($a['path'] ?? '/', '/') === rtrim($b['path'] ?? '/', '/')
            && ($a['query'] ?? '') === ($b['query'] ?? '');
    }

    private function validNext(string $url, string $next, int $current): bool
    {
        try {
            $parts = app(PublicUrl::class)->parts($url);
            $path = $parts['path'] ?? '/';
            if (preg_match('~/page/(\d+)/?$~D', $path, $match) && (int) $match[1] !== $current) {
                return false;
            }
            if (! isset($match[1]) && $current !== 1) {
                return false;
            }
            $base = preg_replace('~/page/\d+/?$~D', '/', $path);
            $expected = $parts['scheme'].'://'.$parts['host'].rtrim($base, '/').'/page/'.($current + 1).'/'
                .(isset($parts['query']) ? '?'.$parts['query'] : '');

            return $this->samePage($expected, $next);
        } catch (\Throwable) {
            return false;
        }
    }
}
