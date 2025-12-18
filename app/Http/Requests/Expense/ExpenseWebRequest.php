<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'expense_type_id' => 'required|exists:expense_types,id',

            // Ajuste: ahora validamos los dos campos generados por el componente
            'amount_amount' => 'required|numeric|min:0',
            'amount_currency' => 'required|integer|in:1,2',

            'payment_type' => 'required|integer|in:1,2,3,4',
            'reference' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'Debe seleccionar una sucursal.',
            'expense_type_id.required' => 'Debe seleccionar un tipo de gasto.',

            'amount_amount.required' => 'Debe ingresar un monto.',
            'amount_amount.numeric' => 'El monto debe ser numérico.',
            'amount_currency.required' => 'Debe seleccionar una moneda.',
            'amount_currency.in' => 'La moneda seleccionada no es válida.',

            'payment_type.required' => 'Debe seleccionar una forma de pago.',
            'payment_type.in' => 'La forma de pago seleccionada no es válida.',
        ];
    }
}
