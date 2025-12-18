<?php

namespace App\Services\Expense;

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
        $expenses = Expense::with(['user', 'branch', 'expenseType'])
            ->orderByDesc('created_at')
            ->get();

        return $expenses->map(function ($expense, $index) {
            return [
                'id' => $expense->id,                               // Oculto pero usable en data-id
                'number' => $index + 1,                             // Columna visible #
                'user' => $expense->user->name ?? '-',              // Usuario que registró el gasto
                'branch' => $expense->branch->name ?? '-',          // Nombre de la sucursal
                'expense_type' => $expense->expenseType->name ?? '-', // Tipo de gasto (ej: Luz, Agua)
                'amount' => $this->formatExpenseAmount($expense),
                'payment_type' => $this->formatStatusBadge($expense->payment_type->label(), PaymentType::class),
                'reference' => $expense->reference ?? '-',          // Factura o referencia
                'created_at' => $expense->created_at->format('d/m/Y H:i'), // Fecha de registro
            ];
        })->toArray();
    }

    private function formatExpenseAmount(Expense $expense, string $class = 'fw-bold text-primary'): string
    {
        if (!$expense->currency) {
            return '<span class="text-muted">-</span>';
        }
        $symbol = $expense->currency->symbol();
        $formatted = number_format($expense->amount, 2, ',', '.');
        return sprintf('<span class="%s">%s %s</span>', $class, $symbol, $formatted);
    }
}
