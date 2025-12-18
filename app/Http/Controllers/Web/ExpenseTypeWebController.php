<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\BaseExpenseTypeController;
use App\Http\Requests\ExpenseType\ExpenseTypeWebRequest;
use App\Services\ExpenseType\ExpenseTypeDataTableService;

class ExpenseTypeWebController extends BaseExpenseTypeController
{
    public function index(ExpenseTypeDataTableService $dataTableService)
    {
        $expenseTypes = $dataTableService->getAllExpenseTypesForDataTable();
        return view('expense_types.index', compact('expenseTypes'));
    }


    public function create()
    {
        return view('expense_types.create');
    }

    public function store(ExpenseTypeWebRequest $request)
    {
        $this->expenseTypeService->createExpenseType($request->validated());
        return redirect()->route('expense_types.index')
            ->with('success', 'Tipo de gasto creado correctamente');
    }

    public function edit($id)
    {
        $expenseType = $this->expenseTypeService->getExpenseTypeById($id);
        return view('expense_types.edit', compact('expenseType'));
    }

    public function update(ExpenseTypeWebRequest $request, $id)
    {
        $this->expenseTypeService->updateExpenseType($id, $request->validated());
        return redirect()->route('expense_types.index')
            ->with('success', 'Tipo de gasto actualizado correctamente');
    }

    public function destroy($id)
    {
        $this->expenseTypeService->deleteExpenseType($id);
        return redirect()->route('expense_types.index')
            ->with('success', 'Tipo de gasto eliminado correctamente');
    }
}
