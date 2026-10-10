<?php

namespace App\Services\Catalogs;

use RuntimeException;

/** Verified public Product JSON-LD on i2C and UNV; no name-based parsing or catalog completeness assumption. */
class TiendaNubeAdapter implements CatalogAdapter
{
    public function __construct(private CatalogHttpClient $http) {}

    public function parse(string $html): array
    {
        $xpath = ProductData::dom($html);
        $products = [];
        foreach ($xpath->query('//script[@type="application/ld+json"]') as $script) {
            $document = json_decode($script->textContent, true);
            if (! is_array($document)) {
                continue;
            }
            $this->collect($document, $products);
        }

        return array_values($products);
    }

    private function collect(array $node, array &$products): void
    {
        $types = (array) ($node['@type'] ?? []);
        if (in_array('Product', $types, true)) {
            $offers = $node['offers'] ?? [];
            $offers = isset($offers['@type']) ? [$offers] : $offers;
            if ($offers === []) {
                $offers = [[]];
            }
            foreach ($offers as $offer) {
                if (! is_array($offer)) {
                    continue;
                }
                $product = ProductData::make([
                    'name' => $node['name'] ?? null,
                    'description' => $node['description'] ?? null,
                    'brand' => is_array($node['brand'] ?? null) ? ($node['brand']['name'] ?? null) : ($node['brand'] ?? null),
                    'model' => $node['model'] ?? null,
                    'code' => $node['mpn'] ?? $node['sku'] ?? null,
                    'presentation' => $node['size'] ?? null,
                    'price' => $offer['price'] ?? null,
                    'currency' => $offer['priceCurrency'] ?? null,
                    'availability' => $offer['availability'] ?? null,
                    'image' => is_array($node['image'] ?? null) ? ($node['image'][0] ?? $node['image']['url'] ?? null) : ($node['image'] ?? null),
                    'url' => $offer['url'] ?? $node['url'] ?? $node['mainEntityOfPage']['@id'] ?? null,
                    'details' => $node,
                ]);
                $products[hash('sha256', json_encode($product))] = $product;
            }
        }
        foreach ($node as $value) {
            if (is_array($value)) {
                $this->collect($value, $products);
            }
        }
    }

    public function fetch(string $url): CatalogResult
    {
        $page = $this->http->get($url, ['www.i2cmayorista.com', 'i2cmayorista.com', 'unvarg.com', 'www.unvarg.com']);
        $products = $this->parse($page['body']);
        if ($products === []) {
            throw new RuntimeException('La página no publicó productos JSON-LD legibles. Puede requerir sesión, JavaScript o un adaptador actualizado.');
        }

        return new CatalogResult(array_slice($products, 0, 500), 'partial', 'Listado parcial: solo productos publicados en la URL consultada; no se recorrieron categorías ni paginación. Disponibilidad tomada del JSON-LD del proveedor; verificar en origen ante discrepancias.');
    }
}
