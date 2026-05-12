<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BasePromotionController;
use Illuminate\Http\Request;

class PromotionController extends BasePromotionController
{
    public function index(Request $request)
    {
        $branchId = $request->query('branch_id');

        $promotions = \App\Models\Promotion::active()
            ->when($branchId, function ($query) use ($branchId) {
                return $query->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                });
            })
            ->get();

        return response()->json($promotions);
    }

    public function show($id)
    {
        return response()->json($this->promotionService->getPromotionById($id));
    }
}
