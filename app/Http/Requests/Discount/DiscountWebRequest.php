<?php

namespace App\Http\Requests\Discount;

use App\Enums\DiscountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class DiscountWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => 'required|string|max:100',
            'type'       => ['required', new Enum(DiscountType::class)],
            'value'      => 'required|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'is_active'  => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => '¡Hey! Olvidaste ponerle un nombre al descuento.',
            'name.max'       => 'El nombre es demasiado largo (máximo 100 caracteres).',
            'type.required'  => 'Debes seleccionar si es Monto Fijo o Porcentaje.',
            'value.required' => 'Indica el valor a descontar.',
            'value.min'      => 'El valor no puede ser negativo.',
        ];
    }

    /**
     * Preparar los datos antes de la validación (Opcional)
     * Útil si por ejemplo el checkbox de 'is_active' no envía nada cuando está apagado.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => $this->has('is_active'),
        ]);
    }
}
