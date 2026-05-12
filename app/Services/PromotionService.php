<?php

namespace App\Services;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Collection;

class PromotionService
{
    public function createPromotion(array $data): Promotion
    {
        return Promotion::create($data);
    }

    public function getAllPromotions(): Collection
    {
        return Promotion::with(['branch'])
            ->orderBy('order', 'asc')
            ->get();
    }

    public function getPromotionById($id): Promotion
    {
        return Promotion::with(['branch'])
            ->findOrFail($id);
    }

    public function updatePromotion($id, array $data): Promotion
    {
        $promotion = $this->getPromotionById($id);
        $promotion->update($data);

        return $promotion->fresh();
    }

    public function deletePromotion($id): bool
    {
        $promotion = $this->getPromotionById($id);
        return $promotion->delete();
    }

    /**
     * Obtiene solo las promociones activas para el frontend
     */
    public function getActivePromotions(): Collection
    {
        return Promotion::active()->get();
    }
}