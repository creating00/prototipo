<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SaleService;
use Illuminate\Http\Request;

class SalePaymentController extends Controller
{
    protected SaleService $service;

    public function __construct(SaleService $service)
    {
        $this->service = $service;
    }

    public function store(Request $request, $saleId)
    {
        $payment = $this->service->addPayment($saleId, $request->all());
        return response()->json($payment, 201);
    }
}
