<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class BaseUserController extends Controller
{
    protected UserService $userService;
    use AuthorizesRequests;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
}
