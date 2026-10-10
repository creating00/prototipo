<?php

namespace App\Services\Catalogs;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use RuntimeException;

/** Verified i2C search and Repuestos grids, Product JSON-LD and public navigation. */
class I2cSearchAdapter implements PaginatedCatalogAdapter
{
    public function __construct(private CatalogHttpClient $http, private TiendaNubeAdapter $products, private PublicUrl $urls) {}

    public function page(string $url): array
    {
        $number = $this->pageNumber($url);
        if ($number < 1 || $number > 400) {
            throw new RuntimeException('El listado de i2C admite páginas de 1 a 400.');
        }
        $response = $this->http->get($url, ['www.i2cmayorista.com', 'i2cmayorista.com']);
        if (! $this->sameSearch($url, $response['url']) || $this->pageNumber($response['url']) !== $number) {
            throw new RuntimeException('i2C redirigió a otra categoría, búsqueda o página.');
        }

        return $this->parsePage($response['body'], $response['url']);
    }

    public function parsePage(string $html, string $url): array
    {
        $xpath = ProductData::dom($html);
        $isCategory = $this->listingPath($url) === '/repuestos';
        $grids = $xpath->query($isCategory
            ? '//section[contains(concat(" ", normalize-space(@class), " "), " category-body ")][starts-with(@data-store,"category-grid-")]'
            : '//*[@data-store="search-grid"]');
        $grid = $grids->item(0);
        if ($grids->length !== 1) {
            throw new RuntimeException('i2C no publicó una única grilla reconocible para este listado.');
        }
        $cards = $xpath->query('.//*[@data-component="product-list-item"]', $grid);
        $cardUrls = [];
        foreach ($cards as $card) {
            $link = $xpath->query('.//a[contains(concat(" ", normalize-space(@class), " "), " js-product-item-image-link-private ")][@href]', $card)->item(0);
            $productUrl = PublicUrl::displayLink($link?->getAttribute('href'));
            if ($productUrl) {
                $cardUrls[$productUrl] = true;
            }
        }
        $products = array_values(array_filter($this->products->parse($html), fn ($product) => isset($cardUrls[$product['url'] ?? ''])));
        if ($products === []) {
            throw new RuntimeException('i2C no publicó productos JSON-LD legibles en esta página del listado.');
        }
        $represented = array_unique(array_column($products, 'url'));
        $reason = count($represented) !== $cards->length || count($cardUrls) !== $cards->length
            ? 'No se pudieron extraer todos los productos de la grilla publicada por i2C.' : null;

        $controls = $xpath->query('.//a[contains(concat(" ", normalize-space(@class), " "), " swiper-button-next ")]', $grid);
        $control = $controls->item(0);
        $current = $this->pageNumber($url);
        $pages = null;
        $next = null;
        $endVerified = false;
        if ($controls->length === 1) {
            $counter = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " h4 ")]/span', $control->parentNode->parentNode);
            if ($counter->length === 3 && ctype_digit(trim($counter->item(0)->textContent))
                && trim($counter->item(1)->textContent) === '/' && ctype_digit(trim($counter->item(2)->textContent))) {
                $publishedCurrent = (int) trim($counter->item(0)->textContent);
                $pages = (int) trim($counter->item(2)->textContent);
                if ($publishedCurrent !== $current || $pages < $current) {
                    $reason = 'El contador de i2C no corresponde a la página solicitada.';
                }
            } else {
                $reason ??= 'i2C no publicó un contador de páginas verificable.';
            }
            $disabled = in_array('disabled', preg_split('/\s+/', $control->getAttribute('class')), true);
            if ($control->hasAttribute('href') && ! $disabled) {
                try {
                    $next = (string) UriResolver::resolve(new Uri($url), new Uri($control->getAttribute('href')));
                    if (! $this->sameSearch($url, $next) || $this->pageNumber($next) !== $current + 1 || $current >= ($pages ?? 0)) {
                        throw new RuntimeException('Enlace inesperado');
                    }
                } catch (\Throwable) {
                    $next = null;
                    $reason = 'El enlace siguiente de i2C cambió de dominio, categoría, búsqueda, filtros o número de página.';
                }
            } elseif ($disabled && ! $control->hasAttribute('href') && $current === $pages) {
                $endVerified = true;
            } else {
                $reason ??= 'Falta un enlace siguiente o una señal de final del listado verificable.';
            }
        } else {
            $reason ??= 'No se encontraron controles verificables de paginación de i2C.';
        }

        return ['products' => $products, 'next_url' => $next, 'current_page' => $current, 'total_pages' => $pages,
            'total' => null, 'from' => null, 'to' => null, 'reason' => $reason, 'mode' => 'numbered',
            'end_verified' => $endVerified,
            'notice' => 'Disponibilidad y precios tomados del JSON-LD del proveedor; verificá las condiciones en origen. No se consultan fichas individuales.'];
    }

    private function pageNumber(string $url): int
    {
        $parts = $this->urls->parts($url);
        if (! in_array(strtolower($parts['host']), ['www.i2cmayorista.com', 'i2cmayorista.com'], true)
            || ! preg_match('~^/(?:search|repuestos)(?:/page/([1-9][0-9]*))?/?$~D', $parts['path'] ?? '', $match)) {
            throw new RuntimeException('La URL no corresponde a un listado paginado verificado de i2C.');
        }

        return isset($match[1]) ? (int) $match[1] : 1;
    }

    private function sameSearch(string $left, string $right): bool
    {
        $a = $this->urls->parts($left);
        $b = $this->urls->parts($right);
        $this->pageNumber($right);
        parse_str($a['query'] ?? '', $queryA);
        parse_str($b['query'] ?? '', $queryB);
        ksort($queryA);
        ksort($queryB);

        return $a['scheme'] === $b['scheme'] && strtolower($a['host']) === strtolower($b['host'])
            && $this->listingPath($left) === $this->listingPath($right) && $queryA === $queryB;
    }

    private function listingPath(string $url): string
    {
        return preg_replace('~/page/[1-9][0-9]*$~', '', rtrim(parse_url($url, PHP_URL_PATH) ?? '', '/'));
    }
}
