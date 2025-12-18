<?php

namespace App\Http\Controllers;

use App\Services\ExpenseTypeService;

abstract class BaseExpenseTypeController extends Controller
{
    protected ExpenseTypeService $expenseTypeService;

    public function __construct(ExpenseTypeService $expenseTypeService)
    {
        $this->expenseTypeService = $expenseTypeService;
    }
}
