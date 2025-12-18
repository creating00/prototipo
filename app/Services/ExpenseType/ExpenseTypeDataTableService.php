<?php

namespace App\Services\ExpenseType;

use App\Models\ExpenseType;

class ExpenseTypeDataTableService
{
    /**
     * Obtiene todos los tipos de gasto y los transforma en un arreglo
     * listo para usar en DataTables o vistas.
     */
    public function getAllExpenseTypesForDataTable(): array
    {
        $expenseTypes = ExpenseType::orderBy('name')->get();

        return $expenseTypes->map(function ($expenseType, $index) {
            return [
                'id' => $expenseType->id,                         // Oculto pero usable en data-id
                'number' => $index + 1,                           // Columna visible #
                'name' => $expenseType->name,                     // Nombre del tipo de gasto
                'description' => $expenseType->description ?? '-', // Descripción opcional
                'created_at' => $expenseType->created_at->format('d/m/Y H:i'), // Fecha de creación
                'updated_at' => $expenseType->updated_at->format('d/m/Y H:i'), // Última actualización
            ];
        })->toArray();
    }
}
