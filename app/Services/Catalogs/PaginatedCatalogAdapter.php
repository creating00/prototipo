<?php

namespace App\Services\Catalogs;

interface PaginatedCatalogAdapter
{
    /**
     * Returns one verified public listing page for the queue coordinator.
     * Unknown product totals and ranges stay null. Numbered listings require
     * a published page counter and end_verified to declare completion.
     *
     * @return array{products: array, next_url: ?string, current_page: ?int, total_pages: ?int, total: ?int, from: ?int, to: ?int, reason: ?string, mode?: string, identity_mode?: string, end_verified?: bool, notice?: string}
     */
    public function page(string $url): array;
}
