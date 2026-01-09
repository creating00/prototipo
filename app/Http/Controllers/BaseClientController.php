<?php

namespace App\Http\Controllers;

use App\Services\ClientService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class BaseClientController extends Controller
{
    protected ClientService $clientService;
    use AuthorizesRequests;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }
}
