<?php

namespace App\Services\Catalogs;

class CatalogRegistry
{
    public function __construct(private PublicUrl $urls) {}

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
