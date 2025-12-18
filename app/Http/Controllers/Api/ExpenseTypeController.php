<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseExpenseTypeController;
use App\Http\Requests\ExpenseType\ExpenseTypeApiRequest;

class ExpenseTypeController extends BaseExpenseTypeController
{
    public function index()
    {
        return response()->json($this->expenseTypeService->getAllExpenseTypes());
    }

    public function store(ExpenseTypeApiRequest $request)
    {
        $expenseType = $this->expenseTypeService->createExpenseType($request->validated());
        return response()->json($expenseType, 201);
    }

    public function show($id)
    {
        return response()->json($this->expenseTypeService->getExpenseTypeById($id));
    }

    public function update(ExpenseTypeApiRequest $request, $id)
    {
        $expenseType = $this->expenseTypeService->updateExpenseType($id, $request->validated());
        return response()->json($expenseType);
    }

    public function destroy($id)
    {
        $this->expenseTypeService->deleteExpenseType($id);
        return response()->json(null, 204);
    }
}
