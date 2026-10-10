<?php

namespace App\Services\Catalogs;

class CatalogRegistry
{
    public function __construct(private PublicUrl $urls) {}

    public static function paginatedAdapter(string $url): ?string
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        if (in_array($host, ['www.fast-cheap.com.ar', 'fast-cheap.com.ar'], true)) {
            return FastCheapAdapter::class;
        }
        if (in_array($host, ['www.i2cmayorista.com', 'i2cmayorista.com'], true)
            && preg_match('~^/(?:search|repuestos)(?:/page/[1-9][0-9]*)?/?$~D', parse_url($url, PHP_URL_PATH) ?? '')) {
            return I2cSearchAdapter::class;
        }

        return null;
    }

    public function fetchPage(string $url): array
    {
        $this->urls->parts($url);
        $adapter = self::paginatedAdapter($url);
        if (! $adapter) {
            throw new \RuntimeException('Este sitio no tiene un adaptador de consulta por tandas.');
        }

        return app($adapter)->page($url);
    }

    public function fetch(string $url): CatalogResult
    {
        $host = strtolower($this->urls->parts($url)['host']);
        $adapter = match ($host) {
            'www.i2cmayorista.com', 'i2cmayorista.com', 'unvarg.com', 'www.unvarg.com' => TiendaNubeAdapter::class,
            'syrcelulares.com', 'www.syrcelulares.com' => WooCommerceAdapter::class,
            'docs.google.com' => GoogleSheetAdapter::class,
            'www.fast-cheap.com.ar', 'fast-cheap.com.ar' => FastCheapAdapter::class,
            'catalogo.duxsoftware.com.ar' => DuxAdapter::class,
            default => null,
        };
        if ($adapter) {
            return app($adapter)->fetch($url);
        }

        return new CatalogResult([], 'unsupported', 'Este sitio todavía no tiene un adaptador verificado. La URL queda guardada para incorporar una integración específica.');
    }
}
