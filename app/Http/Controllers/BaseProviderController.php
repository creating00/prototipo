<?php

namespace App\Http\Controllers;

use App\Services\ProviderService;

abstract class BaseProviderController extends Controller
{
    protected ProviderService $providerService;

    public function __construct(ProviderService $providerService)
    {
        $this->providerService = $providerService;
    }
}
