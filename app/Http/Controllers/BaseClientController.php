<?php

namespace App\Http\Controllers;

use App\Services\ClientService;

abstract class BaseClientController extends Controller
{
    protected ClientService $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }
}