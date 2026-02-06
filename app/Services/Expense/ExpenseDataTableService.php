<?php

namespace App\Services\Expense;

use App\Enums\CurrencyType;
use App\Enums\PaymentType;
use App\Models\Expense;
use App\Traits\HasStatusBadge;

class ExpenseDataTableService
{

    use HasStatusBadge;
    /**
     * Obtiene todos los gastos y los transforma en un arreglo
     * listo para usar en DataTables o vistas.
     */
    public function getAllExpensesForDataTable(): array
    {
        // Cambiamos el orden de la consulta por la fecha del gasto real
        $expenses = Expense::with(['branch'])
            ->orderByDesc('date')
            ->get();

        return $expenses->map(function ($expense, $index) {
            return [
                'id'           => $expense->id,
                'number'       => $index + 1,
                'branch'       => $expense->branch->name ?? '-',
                'branch-id'         => $expense->branch_id,
                'date'         => $expense->date ? $expense->date->format('d/m/Y') : '-',
                'amount'       => $this->formatExpenseAmount($expense),
                'currency'       => $expense->currency->value,
                'amount_raw'       => $expense->amount,
                'payment_type' => $this->formatStatusBadge($expense->payment_type->label(), PaymentType::class),
                'payment_type_raw' => $expense->payment_type->value,
                'observation'  => $expense->observation ?? '-',
            ];
        })->toArray();
    }

    private function formatExpenseAmount(Expense $expense): string
    {
        if (!$expense->currency) {
            return '<span class="text-muted">-</span>';
        }

        $colorClass = ($expense->currency->value === CurrencyType::USD->value) ? 'text-primary' : 'text-danger';

        $symbol = $expense->currency->symbol();
        $formatted = number_format($expense->amount, 2, ',', '.');

        return sprintf(
            '<span class="fw-bold %s">%s %s</span>',
            $colorClass,
            $symbol,
            $formatted
        );
    }
}
