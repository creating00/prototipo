<?php

namespace App\Http\Requests\Discount;

use App\Enums\DiscountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class DiscountApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => 'required|string|max:255',
            'type'       => ['required', new Enum(DiscountType::class)],
            'value'      => 'required|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'is_active'  => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'El nombre del descuento es obligatorio.',
            'type.required'  => 'El tipo de descuento es obligatorio.',
            'type'           => 'El tipo de descuento seleccionado no es válido.',
            'value.required' => 'El valor del descuento es obligatorio.',
            'value.numeric'  => 'El valor debe ser un número.',
        ];
    }
}
