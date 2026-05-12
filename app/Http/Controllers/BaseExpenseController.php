<?php

namespace App\Http\Controllers;

use App\Services\ExpenseService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class BaseExpenseController extends Controller
{
    protected ExpenseService $expenseService;
    use AuthorizesRequests;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }
}
