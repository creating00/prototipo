<?php

namespace App\Services;

use App\Models\PromotionImage;
use Illuminate\Support\Collection;
use Illuminate\Http\UploadedFile;
use Exception;

class PromotionImageService
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Crea el registro y procesa la imagen físicamente.
     */
    public function createPromotionImage(array $data, UploadedFile $file): PromotionImage
    {
        $identifier = ($data['branch_id'] ?? 'gen') . '_' . uniqid();
        $folder = 'promotions';

        $path = $this->imageService->processAndStoreImage($file, $identifier, $folder);

        if (!$path) {
            throw new Exception('No se pudo procesar la imagen de la promoción.');
        }

        return PromotionImage::create([
            'branch_id'  => $data['branch_id'] ?? null,
            'image_path' => $path,
            'is_active'  => $data['is_active'] ?? true,
            'ends_at'    => $data['ends_at'] ?? null,
        ]);
    }

    public function getAll(): Collection
    {
        return PromotionImage::with(['branch'])->latest()->get();
    }

    /**
     * Obtiene las imágenes de promoción formateadas para DataTable,
     * filtrando por sucursal si se proporciona un ID.
     */
    public function getPromotionsForDataTable(?int $branchId = null): array
    {
        return PromotionImage::with(['branch'])
            // Aplicamos el filtro de sucursal si existe
            ->when($branchId, function ($query) use ($branchId) {
                return $query->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                        ->orWhereNull('branch_id'); // Opcional: mostrar también las generales
                });
            })
            ->latest()
            ->get()
            ->map(function ($item) {
                return [
                    'id'         => $item->id,
                    'Sucursal'   => $item->branch?->name ?? 'General',
                    'Imagen'     => '<img src="' . asset($item->image_path) . '" width="80" class="img-thumbnail shadow-sm" alt="Banner">',
                    'Estado'     => $item->is_active
                        ? '<span class="badge badge-custom badge-custom-emerald">Activo</span>'
                        : '<span class="badge badge-custom badge-custom-red">Inactivo</span>',
                    'is_active'  => $item->is_active,
                    'image_path' => $item->image_path,
                    'ends_at'    => $item->ends_at?->format('d/m/Y') ?? 'N/A',
                ];
            })
            ->toArray();
    }

    /**
     * Obtiene promociones activas filtradas por sucursal.
     * Retorna solo id, branch_id y url_image.
     */
    public function getActivePromotions(?int $branchId = null): Collection
    {
        return PromotionImage::where('is_active', true)
            ->when($branchId, function ($query) use ($branchId) {
                return $query->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                });
            })
            ->get()
            ->map(function ($promotion) {
                return [
                    'id'        => $promotion->id,
                    'branch_id' => $promotion->branch_id,
                    // Concatenación con APP_URL vía helper asset()
                    'url_image' => asset($promotion->image_path),
                ];
            });
    }

    public function getById($id): PromotionImage
    {
        return PromotionImage::findOrFail($id);
    }

    /**
     * Elimina el registro y el archivo físico usando ImageService.
     */
    public function delete($id): bool
    {
        $item = PromotionImage::findOrFail($id);

        $this->imageService->deleteImageIfLocal($item->image_path);

        return $item->delete();
    }

    /**
     * Alternar estado activo/inactivo
     */
    public function toggleStatus($id): bool
    {
        $item = PromotionImage::findOrFail($id);
        return $item->update(['is_active' => !$item->is_active]);
    }
}
