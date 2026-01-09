<?php

namespace App\Http\Controllers;

use App\Services\BranchService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class BaseBranchController extends Controller
{
    protected BranchService $branchService;
    use AuthorizesRequests;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }
}
