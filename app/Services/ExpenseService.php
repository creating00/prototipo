<?php

namespace App\Services;

use App\Models\Expense;

class ExpenseService
{
    public function createExpense(array $data): Expense
    {
        return Expense::create($data);
    }

    public function getAllExpenses()
    {
        return Expense::with(['user', 'branch', 'expenseType'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getExpenseById($id): Expense
    {
        return Expense::with(['user', 'branch', 'expenseType'])
            ->findOrFail($id);
    }

    public function updateExpense($id, array $data): Expense
    {
        $expense = $this->getExpenseById($id);
        $expense->update($data);

        return $expense->fresh();
    }

    public function deleteExpense($id): bool
    {
        $expense = $this->getExpenseById($id);
        return $expense->delete();
    }
}
