<?php

namespace App\Http\Controllers;

use App\Services\ExpenseService;

abstract class BaseExpenseController extends Controller
{
    protected ExpenseService $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }
}
