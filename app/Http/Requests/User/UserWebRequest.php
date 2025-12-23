<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user'); // Obtiene el ID si es un update

        return [
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$userId}",
            'branch_id' => 'required|exists:branches,id',
            // 'status' => 'required|string|in:active,inactive',
            'password' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'confirmed',
                Password::min(8),
            ],
            'role' => [
                'required',
                'string',
                'exists:roles,name',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.unique' => 'Este correo ya está registrado.',
            'branch_id.required' => 'Debe asignar una sucursal.',
            'password.required' => 'La contraseña es obligatoria para nuevos usuarios.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
