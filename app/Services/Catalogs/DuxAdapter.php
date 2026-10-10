<?php

namespace App\Services\Catalogs;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use RuntimeException;
use Throwable;

/** Dux publishes Product props in Next.js Flight records alongside the visible placeholders. */
class DuxAdapter implements CatalogAdapter
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
