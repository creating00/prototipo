<?php

namespace App\Http\Controllers;

use App\Services\BranchService;

abstract class BaseBranchController extends Controller
{
    protected BranchService $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }
}
