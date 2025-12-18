<?php

namespace App\Services;

use App\Models\ExpenseType;

class ExpenseTypeService
{
    public function createExpenseType(array $data): ExpenseType
    {
        return ExpenseType::create($data);
    }

    public function getAllExpenseTypes()
    {
        return ExpenseType::orderBy('name')->get();
    }

    public function getExpenseTypeById($id): ExpenseType
    {
        return ExpenseType::findOrFail($id);
    }

    public function updateExpenseType($id, array $data): ExpenseType
    {
        $expenseType = $this->getExpenseTypeById($id);
        $expenseType->update($data);

        return $expenseType->fresh();
    }

    public function deleteExpenseType($id): bool
    {
        $expenseType = $this->getExpenseTypeById($id);
        return $expenseType->delete();
    }
}
