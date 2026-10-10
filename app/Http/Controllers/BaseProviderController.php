<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Services\ProviderService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class BaseProviderController extends Controller
{
    protected ProviderService $providerService;

    use AuthorizesRequests;

    public function __construct(ProviderService $providerService)
    {
        $this->providerService = $providerService;
    }

    protected function authorizeCatalogUrlChanges(Request $request): void
    {
        if ($request->hasAny(['catalog_url', 'catalog_urls', 'catalog_urls_text'])) {
            $this->authorize('manageCatalogUrls', Provider::class);
        }
    }
}
