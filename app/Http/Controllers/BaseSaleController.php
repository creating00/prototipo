<?php

namespace App\Http\Controllers;

use App\Services\SaleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class BaseSaleController extends Controller
{
    protected SaleService $saleService;
    use AuthorizesRequests;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    protected function success($data, int $status = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $data
        ], $status);
    }

    protected function error(string $message, int $status = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $status);
    }
}
