<?php

namespace App\Services\Catalogs;

use RuntimeException;

/** Specific verified multi-block layout of the supplied sheet, not arbitrary spreadsheets. */
class GoogleSheetAdapter implements CatalogAdapter
{
    public const DOCUMENT = '1c39kYssBH8TWE4JPZ8c4xy9OJ4RnsCme';

    public function __construct(private CatalogHttpClient $http) {}

    public function parse(string $csv, string $url): array
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csv);
        rewind($stream);
        $products = [];
        $rowNumber = 0;
        while (($row = fgetcsv($stream, 0, ',', '"', '')) !== false) {
            $rowNumber++;
            for ($column = 0; $column < count($row) - 1; $column++) {
                $name = trim($row[$column]);
                $price = trim($row[$column + 1]);
                if ($name !== '' && preg_match('/^\$\s*\d{1,3}(?:\.\d{3})*(?:,\d{2})?$/D', $price)) {
                    $products[] = ProductData::make([
                        'name' => $name,
                        'price' => str_replace(',', '.', str_replace(['$', ' ', '.'], '', $price)),
                        'url' => $url,
                        'details' => ['fila' => $rowNumber, 'columna' => $column + 1, 'precio_publicado' => $price],
                    ]);
                    $column++;
                }
            }
        }
        fclose($stream);

        return $products;
    }

    public function fetch(string $url): CatalogResult
    {
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $query);
        parse_str($parts['fragment'] ?? '', $fragment);
        $gid = (string) ($query['gid'] ?? $fragment['gid'] ?? '');
        if (! str_starts_with($parts['path'] ?? '', '/spreadsheets/d/'.self::DOCUMENT.'/') || $gid !== '1972998278') {
            return new CatalogResult([], 'unsupported', 'Solo está verificado el documento inicial y la pestaña 1972998278. Otra hoja requiere un adaptador de columnas específico.');
        }
        $response = $this->http->get('https://docs.google.com/spreadsheets/d/'.self::DOCUMENT.'/export?format=csv&gid='.$gid, ['docs.google.com', '*.googleusercontent.com']);
        $products = $this->parse($response['body'], $url);
        if ($products === []) {
            throw new RuntimeException('La hoja no es pública o cambió su estructura de bloques producto/precio.');
        }

        return new CatalogResult(array_slice($products, 0, 1000), 'partial', 'Solo la pestaña indicada y las celdas reconocidas de producto/precio, hasta 1000 productos. El símbolo $ no identifica una moneda: se conserva sin inferir ARS ni USD.');
    }
}
