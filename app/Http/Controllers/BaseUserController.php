<?php

namespace App\Http\Controllers;

use App\Services\UserService;

abstract class BaseUserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
}
