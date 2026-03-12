<?php

namespace App\Http\Requests\Promotion;

use Illuminate\Foundation\Http\FormRequest;

class PromotionWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'nullable|exists:branches,id',
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'label' => 'nullable|string|max:255',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'buttons' => 'nullable|array',
            'buttons.*.text' => 'required_with:buttons|string|max:50',
            'buttons.*.url' => 'required_with:buttons|string',
            'buttons.*.style' => 'nullable|string|max:30',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título de la promoción es obligatorio.',
            'buttons.*.text.required_with' => 'Cada botón debe tener un texto.',
            'buttons.*.url.required_with' => 'Cada botón debe tener un enlace (URL).',
            'branch_id.exists' => 'La sucursal seleccionada no es válida.',
        ];
    }

    /**
     * Preparar datos para validación
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->has('is_active'),
        ]);
    }
}
