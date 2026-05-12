<?php

namespace App\Http\Requests\BankAccount;

use Illuminate\Foundation\Http\FormRequest;

class BankAccountWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'bank_id' => 'required|exists:banks,id',
            'alias' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'cbu' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Debe seleccionar un usuario.',
            'bank_id.required' => 'Debe seleccionar un banco.',
        ];
    }
}
