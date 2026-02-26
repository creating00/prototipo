<?php

namespace App\Imports;

use App\Models\Expense;
use App\Models\ExpenseType;
use App\Services\ExpenseService;
use App\Enums\CurrencyType;
use App\Enums\PaymentType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ExpensesImport implements ToModel, WithHeadingRow
{
    private $branchId;
    private $expenseService;

    public function __construct($branchId)
    {
        $this->branchId = $branchId;
        $this->expenseService = app(ExpenseService::class);
    }

    public function model(array $row)
    {
        // Validacion campos minimos
        if (empty($row['monto']) || empty($row['fecha'])) {
            return null;
        }

        // $expenseTypeId = $this->resolveExpenseType($row['tipo_gasto'] ?? 'General');

        $data = [
            'user_id'         => Auth::id() ?? 1,
            'branch_id'       => $this->branchId,
            // 'expense_type_id' => $expenseTypeId, // Ignorado
            'amount'          => (float) $row['monto'],
            'currency'        => $this->parseCurrency($row['moneda'] ?? 'ARS'),
            'payment_type'    => $this->parsePaymentType($row['metodo_pago'] ?? 'Efectivo'),
            //'reference'       => $row['referencia'] ?? null,
            'date'            => $this->parseDate($row['fecha']),
            'observation'     => $row['observacion'] ?? null,
        ];

        return $this->expenseService->createExpense($data);
    }

    /**
     * Logica ignorada actualmente
     */
    private function resolveExpenseType(?string $name): int
    {
        $name = trim($name ?? 'General');

        $type = ExpenseType::firstOrCreate(
            ['name' => $name],
            ['description' => 'Creado mediante importación']
        );

        return $type->id;
    }

    private function parseCurrency(?string $value): int
    {
        $value = strtoupper(trim($value ?? 'ARS'));
        return ($value === 'USD') ? CurrencyType::USD->value : CurrencyType::ARS->value;
    }

    private function parsePaymentType(?string $value): int
    {
        $value = mb_strtolower(trim($value ?? 'efectivo'));

        return match (true) {
            str_contains($value, 'efectivo'), str_contains($value, 'cash') => PaymentType::Cash->value,
            str_contains($value, 'tarjeta'), str_contains($value, 'card')   => PaymentType::Card->value,
            str_contains($value, 'transf')                                  => PaymentType::Transfer->value,
            str_contains($value, 'cheque'), str_contains($value, 'check')  => PaymentType::Check->value,
            default => PaymentType::Cash->value,
        };
    }

    private function parseDate($value): Carbon
    {
        try {
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
            }
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return Carbon::today();
        }
    }
}
