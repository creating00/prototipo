<?php

namespace App\ViewModels;

use App\Enums\CurrencyType;
use App\Models\Expense;
use Illuminate\Support\Collection;

class ExpenseFormData
{
    public function __construct(
        public readonly ?Expense $expense,
        public readonly Collection $branches,
        public readonly Collection $expenseTypes,
        public readonly Collection $provinces,
        public readonly array $currencyOptions,
        public readonly array $paymentOptions,
        public readonly ?int $branchUserId,
    ) {}

    /**
     * Devuelve la moneda del gasto actual o un valor por defecto.
     */
    public function currency(): int
    {
        // Si existe el gasto y tiene moneda, extraemos el valor del Enum
        if ($this->expense && $this->expense->currency) {
            return $this->expense->currency->value;
        }

        return $this->defaultCurrency();
    }

    /**
     * Moneda por defecto para nuevos gastos.
     */
    public function defaultCurrency(): int
    {
        return CurrencyType::ARS->value;
    }
}
