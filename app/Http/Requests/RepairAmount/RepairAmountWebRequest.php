<?php

namespace App\Http\Requests\RepairAmount;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\RepairType;

class RepairAmountWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id'   => 'required|exists:branches,id',
            'repair_type' => 'required|integer|in:' . implode(
                ',',
                array_map(fn($case) => $case->value, RepairType::cases())
            ),
            'amount'      => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required'   => 'Debe seleccionar una sucursal.',
            'branch_id.exists'     => 'La sucursal seleccionada no es válida.',

            'repair_type.required' => 'Debe seleccionar un tipo de reparación.',
            'repair_type.in'       => 'El tipo de reparación seleccionado no es válido.',

            'amount.required'      => 'Debe ingresar un monto.',
            'amount.numeric'       => 'El monto debe ser numérico.',
            'amount.min'           => 'El monto no puede ser negativo.',
        ];
    }
}
