<?php

namespace App\Http\Controllers;

use App\Services\ExpenseTypeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class BaseExpenseTypeController extends Controller
{
    protected ExpenseTypeService $expenseTypeService;
    use AuthorizesRequests;
    
    public function __construct(ExpenseTypeService $expenseTypeService)
    {
        $this->expenseTypeService = $expenseTypeService;
    }
}
