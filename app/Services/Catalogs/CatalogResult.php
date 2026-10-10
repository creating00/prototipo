<?php

namespace App\Services\Catalogs;

class CatalogResult
{
    public function __construct(public array $products, public string $status, public string $message) {}
}
