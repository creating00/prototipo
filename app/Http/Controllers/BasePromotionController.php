<?php

namespace App\Http\Controllers;

use App\Services\PromotionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class BasePromotionController extends Controller
{
    use AuthorizesRequests;
    protected $promotionService;

    public function __construct(PromotionService $promotionService)
    {
        $this->promotionService = $promotionService;
    }
}
