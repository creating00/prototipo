<?php

namespace App\Http\Requests\ProviderOrder;

use Illuminate\Foundation\Http\FormRequest;

class ProviderOrderWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_id'            => 'required|exists:providers,id',
            'status'                 => 'required|integer',
            'order_date'             => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'items'                  => 'required|array|min:1',
            'items.*.provider_product_id' => 'required|exists:provider_products,id',
            'items.*.quantity'       => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'provider_id.required'    => 'Debe seleccionar un proveedor.',
            'items.required'          => 'Debe agregar al menos un producto a la orden.',
            'items.*.quantity.min'    => 'La cantidad debe ser al menos 1.',
            'expected_delivery_date.after_or_equal' => 'La fecha de entrega no puede ser anterior a la fecha de la orden.',
        ];
    }
}
