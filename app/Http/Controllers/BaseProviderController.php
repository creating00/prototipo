<?php

namespace App\Http\Controllers;

use App\Services\ProviderService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class BaseProviderController extends Controller
{
    protected ProviderService $providerService;
    use AuthorizesRequests;
    
    public function __construct(ProviderService $providerService)
    {
        $this->providerService = $providerService;
    }
}
