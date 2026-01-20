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
            'expense_type_id' => 'nullable|exists:expense_types,id',
            'amount_amount' => 'required|numeric|min:0',
            'amount_currency' => 'required|integer|in:1,2',
            'payment_type' => 'required|integer|in:1,2,3,4',
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'observation' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'amount_amount.required' => 'Debe ingresar un monto.',
            'amount_amount.numeric' => 'El monto debe ser numérico.',
            'amount_currency.required' => 'Debe seleccionar una moneda.',
            'payment_type.required' => 'Debe seleccionar una forma de pago.',
            'date.required' => 'La fecha del gasto es obligatoria.',
            'date.date' => 'El formato de fecha es inválido.',
        ];
    }
}
