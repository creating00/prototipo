<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\BasePromotionController;
use App\Http\Requests\Promotion\PromotionWebRequest;
use App\Models\Promotion;
use App\Models\Branch;
use App\Services\Promotion\PromotionDataTableService;
use App\Traits\AuthTrait;
use Illuminate\Http\Request;

class PromotionWebController extends BasePromotionController
{
    use AuthTrait;

    public function index(PromotionDataTableService $dataTableService)
    {
        $this->authorize('viewAny', Promotion::class);

        $rowData = $dataTableService->getAllPromotionsForDataTable();

        $headers = ['#', 'Sucursal', 'Título', 'Estado'];
        $hiddenFields = ['id', 'buttons', 'subtitle', 'label'];

        return view('admin.promotion.index', compact('rowData', 'headers', 'hiddenFields'));
    }

    public function create()
    {
        $this->authorize('create', Promotion::class);

        $formData = new \App\ViewModels\PromotionFormData(
            promotion: null,
            branches: \App\Models\Branch::pluck('name', 'id'),
            branchUserId: $this->currentBranchId(),
        );

        return view('admin.promotion.create', compact('formData'));
    }



    public function store(PromotionWebRequest $request)
    {
        $this->authorize('create', Promotion::class);

        $this->promotionService->createPromotion($request->validated());

        return redirect()->route('web.promotions.index')
            ->with('success', 'Promoción creada correctamente');
    }

    public function edit($id)
    {
        $promotion = $this->promotionService->getPromotionById($id);
        $this->authorize('update', $promotion);

        $formData = new \App\ViewModels\PromotionFormData(
            promotion: $promotion,
            branches: \App\Models\Branch::pluck('name', 'id'),
            branchUserId: $this->currentBranchId(),
        );

        return view('admin.promotion.edit', compact('formData'));
    }

    public function update(PromotionWebRequest $request, $id)
    {
        $promotion = $this->promotionService->getPromotionById($id);
        $this->authorize('update', $promotion);

        $this->promotionService->updatePromotion($id, $request->validated());

        return redirect()->route('web.promotions.index')
            ->with('success', 'Promoción actualizada correctamente');
    }

    public function destroy($id)
    {
        $promotion = $this->promotionService->getPromotionById($id);
        $this->authorize('delete', $promotion);

        $this->promotionService->deletePromotion($id);

        return redirect()->route('web.promotions.index')
            ->with('success', 'Promoción eliminada correctamente');
    }
}
