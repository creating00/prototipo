<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PromotionImage;
use App\Models\Branch;
use App\Services\PromotionImageService;
use App\Traits\AuthTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class PromotionImageWebController extends Controller
{
    use AuthTrait, AuthorizesRequests;

    protected $promotionImageService;

    public function __construct(PromotionImageService $promotionImageService)
    {
        $this->promotionImageService = $promotionImageService;
    }

    public function index()
    {
        $this->authorize('viewAny', PromotionImage::class);

        $branchId = $this->currentBranchId();

        $rowData = $this->promotionImageService->getPromotionsForDataTable($branchId);

        $headers = ['Sucursal', 'Imagen', 'Estado'];
        $hiddenFields = ['id', 'is_active', 'image_path', 'ends_at'];

        return view('admin.promotion_images.index', compact('rowData', 'headers', 'hiddenFields'));
    }

    public function create()
    {
        $this->authorize('create', PromotionImage::class);

        $branches = Branch::pluck('name', 'id');
        $currentBranchId = $this->currentBranchId();

        return view('admin.promotion_images.create', compact('branches', 'currentBranchId'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', PromotionImage::class);

        // Solo validamos la imagen, ya que el branch_id lo manejamos internamente
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        try {
            // Obtenemos el ID de la sucursal desde el AuthTrait
            $branchId = $this->currentBranchId();

            $this->promotionImageService->createPromotionImage(
                ['branch_id' => $branchId],
                $request->file('image')
            );

            return redirect()->route('web.promotions.index')
                ->with('success', 'Banner subido y optimizado correctamente');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al subir: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $promotionImage = PromotionImage::findOrFail($id);
        $this->authorize('delete', $promotionImage);

        $this->promotionImageService->delete($id);

        return redirect()->route('web.promotions.index')
            ->with('success', 'Imagen eliminada correctamente');
    }

    /**
     * Método para el toggle de activación (AJAX o POST rápido)
     */
    public function toggleStatus($id)
    {
        $promotionImage = PromotionImage::findOrFail($id);
        $this->authorize('update', $promotionImage);

        $this->promotionImageService->toggleStatus($id);

        return back()->with('success', 'Estado actualizado');
    }
}
