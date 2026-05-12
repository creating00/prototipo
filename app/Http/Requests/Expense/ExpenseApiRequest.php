<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'expense_type_id' => 'nullable|exists:expense_types,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|integer|in:1,2',
            'payment_type' => 'required|integer|in:1,2,3,4',
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'observation' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'La sucursal es obligatoria.',
            'amount.required' => 'El monto es obligatorio.',
            'currency.required' => 'La moneda es obligatoria.',
            'payment_type.required' => 'El tipo de pago es obligatorio.',
            'date.required' => 'La fecha es obligatoria.',
            'date.date' => 'La fecha enviada no es válida.',
        ];
    }
}
