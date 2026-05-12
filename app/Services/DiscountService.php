<?php

namespace App\Services;

use App\Models\Discount;
use App\Traits\AuthTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class DiscountService
{
    use AuthTrait;
    /**
     * Obtener todos los descuentos (para el listado del CRUD)
     */
    public function getAll()
    {
        return Discount::orderBy('created_at', 'desc')->get();
    }

    public function getAllDiscountsForDataTable()
    {
        $discounts = $this->getAll(); // Obtiene la colección de modelos

        return $discounts->map(function ($discount, $index) {
            return [
                'id'        => $discount->id,               // Se ocultará vía $hiddenFields
                'number'    => $index + 1,                  // Columna visible #
                'name'      => $discount->name,
                'type'      => $discount->type->label(),    // Ej: "Porcentaje"
                'value'     => $discount->type->symbol() . ' ' . $discount->value, // Ej: "% 15" o "$ 500"
                'status'    => $discount->is_active ? 'Activo' : 'Inactivo',
            ];
        })->toArray();
    }

    /**
     * Crear un nuevo descuento
     */
    public function create(array $data): Discount
    {
        try {
            return DB::transaction(function () use ($data) {
                // Forzamos que created_by sea el usuario autenticado si no viene en la data
                $data['created_by'] = $data['created_by'] ?? $this->userId();

                return Discount::create($data);
            });
        } catch (Exception $e) {
            Log::error("Error creando descuento: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualizar un descuento existente
     */
    public function update(Discount $discount, array $data): bool
    {
        try {
            return $discount->update($data);
        } catch (Exception $e) {
            Log::error("Error actualizando descuento ID {$discount->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Eliminar un descuento (Soft Delete)
     */
    public function delete(Discount $discount): bool
    {
        try {
            return $discount->delete();
        } catch (Exception $e) {
            Log::error("Error eliminando descuento ID {$discount->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Alternar el estado de activo/inactivo
     */
    public function toggleStatus(Discount $discount): bool
    {
        return $discount->update([
            'is_active' => !$discount->is_active
        ]);
    }

    // --- Tus métodos existentes optimizados ---

    public function getActiveForSale()
    {
        return Discount::active()
            ->orderBy('name')
            ->get();
    }

    public function getForSelect()
    {
        return $this->getActiveForSale()
            ->pluck('display_name', 'id');
    }

    public function getValueMap(): array
    {
        return Discount::active()
            ->get()
            ->mapWithKeys(function (Discount $discount) {
                return [
                    $discount->id => [
                        'type'  => $discount->type->value,
                        'value' => (float) $discount->value,
                        'max'   => $discount->max_amount !== null
                            ? (float) $discount->max_amount
                            : null,
                    ],
                ];
            })
            ->toArray();
    }
}
