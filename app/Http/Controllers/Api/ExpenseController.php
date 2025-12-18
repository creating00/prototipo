<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseExpenseController;
use App\Http\Requests\Expense\ExpenseApiRequest;
use Illuminate\Http\Request;

class ExpenseController extends BaseExpenseController
{
    public function index()
    {
        return response()->json($this->expenseService->getAllExpenses());
    }

    public function store(ExpenseApiRequest $request)
    {
        $expense = $this->expenseService->createExpense($request->validated());
        return response()->json($expense, 201);
    }

    public function show($id)
    {
        return response()->json($this->expenseService->getExpenseById($id));
    }

    public function update(ExpenseApiRequest $request, $id)
    {
        $expense = $this->expenseService->updateExpense($id, $request->validated());
        return response()->json($expense);
    }

    public function destroy($id)
    {
        $this->expenseService->deleteExpense($id);
        return response()->json(null, 204);
    }
}
