<?php

namespace App\Services\Catalogs;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use RuntimeException;
use Throwable;

/** Dux publishes Product props in Next.js Flight records alongside the visible placeholders. */
class DuxAdapter implements CatalogAdapter, PaginatedCatalogAdapter
{
    public function __construct(private CatalogHttpClient $http) {}

    public function parse(string $html, string $sourceUrl): array
    {
        $xpath = ProductData::dom($html);
        $flight = '';
        foreach ($xpath->query('//script[not(@src)]') as $script) {
            $text = trim($script->textContent);
            $prefix = 'self.__next_f.push(';
            if (! str_starts_with($text, $prefix)) {
                continue;
            }
            $text = rtrim($text, ';');
            if (! str_ends_with($text, ')')) {
                continue;
            }
            // Decode JSON arguments only. Never evaluate JavaScript from a supplier.
            $chunk = json_decode(substr($text, strlen($prefix), -1), true);
            if (is_array($chunk) && ($chunk[0] ?? null) === 1 && is_string($chunk[1] ?? null)) {
                $flight .= $chunk[1];
            }
        }
        $products = [];
        foreach (explode("\n", $flight) as $line) {
            // Import records, text records and references are not product JSON records.
            if (! preg_match('/^[0-9a-f]+:([\[{].*)$/D', $line, $matches)) {
                continue;
            }
            $record = json_decode($matches[1], true);
            if (is_array($record)) {
                $this->collect($record, $sourceUrl, $products);
            }
        }

        return array_values($products);
    }

    private function collect(array $node, string $sourceUrl, array &$products): void
    {
        $row = $node['product'] ?? null;
        if (is_array($row) && is_scalar($row['item'] ?? null) && isset($row['sku'])) {
            $stock = $row['stock_disponible'] ?? null;
            $product = ProductData::make([
                'name' => $row['item'],
                'description' => $row['descripcion'] ?? null,
                'code' => $row['sku'],
                'price' => $row['total'] ?? null,
                // Dux says PESOS, not ARS: preserve the published label without inferring ISO currency.
                'currency' => $row['moneda'] ?? null,
                'availability' => is_numeric($stock) ? 'Stock publicado: '.$stock : null,
                'image' => $row['imagenes'][0] ?? $row['url'] ?? null,
                // The field named url is an image, not a product permalink. The page is the source.
                'url' => $sourceUrl,
                'details' => $row,
            ]);
            $products[hash('sha256', json_encode($row))] = $product;
        }
        foreach ($node as $value) {
            if (is_array($value)) {
                $this->collect($value, $sourceUrl, $products);
            }
        }
    }

    public function nextPage(string $html, string $currentUrl): ?string
    {
        $current = parse_url($currentUrl);
        parse_str($current['query'] ?? '', $currentQuery);
        $page = (int) ($currentQuery['page'] ?? 1);
        unset($currentQuery['page']);
        foreach (ProductData::dom($html)->query('//a[@href]') as $link) {
            $url = (string) UriResolver::resolve(new Uri($currentUrl), new Uri($link->getAttribute('href')));
            $candidate = parse_url($url);
            parse_str($candidate['query'] ?? '', $query);
            if (($candidate['host'] ?? null) !== 'catalogo.duxsoftware.com.ar'
                || ($candidate['scheme'] ?? null) !== ($current['scheme'] ?? null)
                || ($candidate['path'] ?? null) !== ($current['path'] ?? null)
                || (string) ($query['page'] ?? '') !== (string) ($page + 1)) {
                continue;
            }
            unset($query['page']);
            if ($query == $currentQuery) {
                return $url;
            }
        }

        return null;
    }

    public function page(string $url): array
    {
        $parts = $this->listingParts($url);
        if ($parts['page'] > 400) {
            throw new RuntimeException('Dux admite páginas de 1 a 400 por consulta.');
        }
        $response = $this->http->get($url, ['catalogo.duxsoftware.com.ar']);
        if (! $this->sameListing($url, $response['url']) || $this->listingParts($response['url'])['page'] !== $parts['page']) {
            throw new RuntimeException('Dux redirigió a otro catálogo, rubro, búsqueda o página.');
        }

        return $this->parsePage($response['body'], $response['url']);
    }

    public function parsePage(string $html, string $url): array
    {
        $current = $this->listingParts($url)['page'];
        $products = $this->parse($html, $url);
        if ($products === []) {
            throw new RuntimeException('Dux no publicó datos de productos legibles en esta página. La búsqueda puede estar vacía o el formato puede haber cambiado.');
        }
        $xpath = ProductData::dom($html);
        $cards = $xpath->query('//*[@data-testid="card-product-info"]');
        $reason = count($products) !== $cards->length ? 'No se pudieron extraer todos los productos de la página publicada por Dux.' : null;
        if (collect($products)->contains(fn ($product) => ($product['code'] ?? null) === null || $product['code'] === '')) {
            $reason ??= 'Dux publicó productos sin SKU verificable; el listado requiere revisión.';
        }
        $next = null;
        $end = false;
        $controls = $xpath->query('//*[self::a or self::div][i[contains(concat(" ", normalize-space(@class), " "), " pi-angle-right ")]]');
        if ($controls->length !== 1) {
            $reason ??= 'Dux no publicó un control siguiente o final reconocible.';
        } else {
            $control = $controls->item(0);
            $pagination = $control->parentNode;
            $active = $xpath->query('.//div[contains(concat(" ", normalize-space(@class), " "), " z-10 ")]', $pagination);
            if ($active->length !== 1 || trim($active->item(0)->textContent) !== (string) $current) {
                $reason ??= 'La página activa publicada por Dux no coincide con la solicitada.';
            }
            if ($control->nodeName === 'a' && $control->hasAttribute('href')) {
                try {
                    $next = (string) UriResolver::resolve(new Uri($url), new Uri($control->getAttribute('href')));
                    if (! $this->sameListing($url, $next) || $this->listingParts($next)['page'] !== $current + 1) {
                        throw new RuntimeException('Enlace inesperado');
                    }
                } catch (Throwable) {
                    $next = null;
                    $reason = 'El enlace siguiente de Dux cambió de dominio, catálogo, rubro, filtros o número de página.';
                }
            } elseif ($control->nodeName === 'div' && in_array('pointer-events-none', preg_split('/\s+/', $control->getAttribute('class')), true)) {
                $end = true;
            } else {
                $reason ??= 'No se pudo verificar el final del listado de Dux.';
            }
        }

        return ['products' => $products, 'next_url' => $next, 'current_page' => $current, 'total_pages' => null,
            'total' => null, 'from' => null, 'to' => null, 'reason' => $reason, 'mode' => 'linked', 'identity_mode' => 'dux_sku',
            'end_verified' => $end,
            'notice' => 'Dux: moneda conservada como se publica; el enlace de origen lleva a la página del catálogo. No se consultan fichas ni detalles de variantes.'];
    }

    private function listingParts(string $url): array
    {
        $parts = app(PublicUrl::class)->parts($url);
        parse_str($parts['query'] ?? '', $query);
        $page = $query['page'] ?? '1';
        if ($parts['host'] !== 'catalogo.duxsoftware.com.ar' || ! preg_match('~^/[a-zA-Z0-9_-]+/?$~D', $parts['path'] ?? '')
            || ! is_scalar($page) || ! preg_match('/^[1-9][0-9]{0,4}$/D', (string) $page)) {
            throw new RuntimeException('Dux: usá la URL pública de un catálogo y un número de página válido.');
        }
        unset($query['page']);
        ksort($query);

        return ['scheme' => $parts['scheme'], 'path' => $parts['path'], 'filters' => $query, 'page' => (int) $page];
    }

    private function sameListing(string $left, string $right): bool
    {
        $a = $this->listingParts($left);
        $b = $this->listingParts($right);

        return $a['scheme'] === $b['scheme'] && $a['path'] === $b['path'] && $a['filters'] === $b['filters'];
    }

    /** Legacy bounded direct fetch; catalog queue jobs use page() and continue up to 400 pages. */
    public function fetch(string $url): CatalogResult
    {
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $query);
        if (! preg_match('~^/[a-zA-Z0-9_-]+/?$~D', $parts['path'] ?? '')
            || ! preg_match('/^[1-9][0-9]{0,4}$/D', (string) ($query['page'] ?? '1'))) {
            return new CatalogResult([], 'unsupported', 'Dux: usá la URL pública de un catálogo y un número de página válido.');
        }
        $startPage = (int) ($query['page'] ?? 1);
        $products = [];
        for ($page = 0; $page < 4; $page++) {
            try {
                $response = $this->http->get($url, ['catalogo.duxsoftware.com.ar']);
                $rows = $this->parse($response['body'], $response['url']);
                if ($rows === []) {
                    throw new RuntimeException('Dux no publicó datos de productos legibles en esta página. La búsqueda puede estar vacía o el formato puede haber cambiado.');
                }
                foreach ($rows as $row) {
                    $products[hash('sha256', json_encode($row['details']))] = $row;
                }
                $next = $this->nextPage($response['body'], $response['url']);
                if ($next === null) {
                    break;
                }
                $url = $next;
            } catch (Throwable $e) {
                if ($products === []) {
                    throw $e;
                }

                return new CatalogResult(array_values($products), 'partial', 'Dux: listado parcial; falló una página posterior. '.$e->getMessage());
            }
        }

        // No total-count contract is published. A missing next link alone does not prove completeness.
        return new CatalogResult(array_values($products), 'partial', 'Dux: datos públicos embebidos, desde la página '.$startPage.', hasta cuatro páginas siguiendo enlaces publicados. No se garantiza el catálogo completo ni se consultan detalles de variantes. La moneda se conserva tal como se publica (por ejemplo PESOS); el enlace de origen lleva a la página del catálogo.');
    }
}
