<?php

namespace App\Services\Promotion;

use App\Models\Promotion;

class PromotionDataTableService
{
    /**
     * Transforma las promociones para el formato de DataTables.
     */
    public function getAllPromotionsForDataTable(): array
    {
        $promotions = Promotion::with(['branch'])
            ->orderBy('order', 'asc')
            ->get();

        return $promotions->map(function ($promotion) {
            return [
                'id'       => $promotion->id,
                'number'   => $promotion->id,
                'branch'   => $promotion->branch->name ?? 'Global',
                'title'    => $promotion->title,
                //'order'    => $promotion->order,
                'status'   => $this->formatStatusBadge($promotion),
                // Campos ocultos
                'buttons'  => json_encode($promotion->buttons),
                'subtitle' => $promotion->subtitle,
                'label'    => $promotion->label,
            ];
        })->toArray();
    }

    private function formatStatusBadge(Promotion $promotion): string
    {
        return $promotion->is_active
            ? '<span class="badge bg-success">Activa</span>'
            : '<span class="badge bg-secondary">Inactiva</span>';
    }
}
