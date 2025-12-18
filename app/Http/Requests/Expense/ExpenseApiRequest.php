<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Aquí puedes aplicar lógica de permisos si lo deseas
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'expense_type_id' => 'required|exists:expense_types,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|integer|in:1,2', // CurrencyType::ARS, ::USD
            'payment_type' => 'required|integer|in:1,2,3,4', // PaymentType enum
            'reference' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'El usuario es obligatorio.',
            'branch_id.required' => 'La sucursal es obligatoria.',
            'expense_type_id.required' => 'El tipo de gasto es obligatorio.',
            'amount.required' => 'El monto es obligatorio.',
            'currency.in' => 'La moneda seleccionada no es válida.',
            'payment_type.in' => 'El tipo de pago seleccionado no es válido.',
        ];
    }
}
