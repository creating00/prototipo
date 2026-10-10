<?php

namespace App\Services\Catalogs;

interface CatalogAdapter
{
    public function fetch(string $url): CatalogResult;
}
