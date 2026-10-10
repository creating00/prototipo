<?php

namespace App\Services\Catalogs;

use RuntimeException;
use Throwable;

/** Public Store API, verified on S&R. No authentication or private administrative API. */
class WooCommerceAdapter implements CatalogAdapter
{
    public function __construct(private CatalogHttpClient $http) {}

    public function parse(array $rows): array
    {
        $products = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['id'], $row['name'])) {
                throw new RuntimeException('Formato inesperado en la API pública de productos.');
            }
            $prices = $row['prices'] ?? [];
            $amount = $prices['price'] ?? null;
            $unit = $prices['currency_minor_unit'] ?? null;
            $price = empty($prices['price_range']) && is_numeric($amount) && is_int($unit) && $unit >= 0 && $unit <= 6
                ? number_format((float) $amount / (10 ** $unit), $unit, '.', '') : null;
            $products[] = ProductData::make([
                'name' => $row['name'], 'description' => ($row['description'] ?? null) ?: ($row['short_description'] ?? null),
                'code' => $row['sku'] ?? null,
                'brand' => implode(', ', array_column($row['brands'] ?? [], 'name')),
                'price' => $price, 'currency' => $prices['currency_code'] ?? null,
                'availability' => $row['stock_availability']['text'] ?? null,
                'image' => $row['images'][0]['src'] ?? null, 'url' => $row['permalink'] ?? null,
                'details' => $row,
            ]);
        }

        return $products;
    }

    public function fetch(string $url): CatalogResult
    {
        $parts = parse_url($url);
        // Only the verified shop root is supported. Never silently ignore a category/search URL.
        if (! in_array($parts['path'] ?? '/', ['', '/'], true) || ! empty($parts['query'])) {
            return new CatalogResult([], 'unsupported', 'S&R: el adaptador actual admite la URL principal https://syrcelulares.com/. Categorías y búsquedas requieren otro adaptador.');
        }
        $products = [];
        $pages = null;
        for ($page = 1; $page <= 4; $page++) {
            try {
                $response = $this->http->get('https://syrcelulares.com/wp-json/wc/store/v1/products?per_page=100&page='.$page, ['syrcelulares.com', 'www.syrcelulares.com']);
                $rows = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($rows) || ! array_is_list($rows)) {
                    throw new RuntimeException('Respuesta de API inesperada.');
                }
                $products = array_merge($products, $this->parse($rows));
                $pages = ctype_digit((string) $response['total_pages']) ? (int) $response['total_pages'] : null;
                if ($pages !== null && $page >= $pages) {
                    return new CatalogResult($products, 'complete', 'Listado obtenido de la API pública, incluyendo todas las páginas declaradas. Productos variables pueden requerir revisar opciones y precios en origen.');
                }
                if ($pages === null && count($rows) < 100) {
                    break;
                }
            } catch (Throwable $e) {
                if ($products === []) {
                    throw $e;
                }

                return new CatalogResult($products, 'partial', 'Listado parcial: falló la página '.$page.'. '.$e->getMessage());
            }
        }

        return new CatalogResult($products, 'partial', 'Listado parcial: límite de cuatro páginas / 400 productos por consulta. No se garantiza haber obtenido todo el catálogo.');
    }
}
