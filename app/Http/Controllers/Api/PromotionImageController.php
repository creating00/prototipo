<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PromotionImageService;
use Illuminate\Http\Request;

class PromotionImageController extends Controller
{
    protected $promotionImageService;

    public function __construct(PromotionImageService $promotionImageService)
    {
        $this->promotionImageService = $promotionImageService;
    }

    public function index(Request $request)
    {
        // Delegar filtrado y formato al servicio
        $promotions = $this->promotionImageService->getActivePromotions($request->query('branch_id'));

        return response()->json($promotions);
    }

    public function show($id)
    {
        return response()->json($this->promotionImageService->getById($id));
    }
}
