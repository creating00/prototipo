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
            'expense_type_id' => 'required|exists:expense_types,id',
            'amount_amount' => 'required|numeric|min:0',
            'amount_currency' => 'required|integer|in:1,2',
            'payment_type' => 'required|integer|in:1,2,3,4',
            'bank_account_id' => 'required_if:payment_type,3|nullable|exists:bank_accounts,id',
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'observation' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'expense_type_id.required' => 'Debe seleccionar un motivo del gasto.',
            'expense_type_id.exists' => 'El motivo seleccionado no es válido.',
            'amount_amount.required' => 'Debe ingresar un monto.',
            'amount_amount.numeric' => 'El monto debe ser numérico.',
            'amount_currency.required' => 'Debe seleccionar una moneda.',
            'payment_type.required' => 'Debe seleccionar una forma de pago.',
            'bank_account_id.required_if' => 'Debe seleccionar una cuenta destino para transferencias.',
            'bank_account_id.exists' => 'La cuenta destino seleccionada no es válida.',
            'date.required' => 'La fecha del gasto es obligatoria.',
            'date.date' => 'El formato de fecha es inválido.',
        ];
    }
}
