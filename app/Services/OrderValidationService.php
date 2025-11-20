<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderValidationService
{
    /**
     * Valida la request para crear/actualizar órdenes
     */
    public function validateOrderRequest(Request $request): array
    {
        $validated = $request->validate([
            'cliente' => 'nullable|array', // Solo para guest
            'cliente.document' => 'required_with:cliente|string',
            'cliente.full_name' => 'required_with:cliente|string',
            'cliente.phone' => 'nullable|string',
            'cliente.address' => 'nullable|string',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:products,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'id' => 'nullable|integer|exists:users,id', // Para usuarios del sistema
            'token' => 'nullable|string' // Para clientes del e-commerce
        ]);

        $this->validateBusinessRules($validated);

        return $validated;
    }

    /**
     * Valida las reglas de negocio
     */
    private function validateBusinessRules(array $validated): void
    {
        // Validar que solo venga uno de los dos
        if (isset($validated['id']) && isset($validated['token'])) {
            abort(422, 'Solo se debe enviar id (user) o token (cliente), no ambos');
        }

        // Validar que si viene token, no venga cliente
        if (isset($validated['token']) && isset($validated['cliente'])) {
            abort(422, 'Cuando se envía token, no se deben enviar datos del cliente');
        }

        // Validar que si no viene token, venga cliente
        if (!isset($validated['token']) && !isset($validated['cliente'])) {
            abort(422, 'Cuando no se envía token, se deben enviar datos del cliente');
        }
    }
}
