<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseSaleController;
use Illuminate\Http\Request;

class SaleController extends BaseSaleController
{
    public function index()
    {
        return $this->success($this->saleService->getAllSales());
    }

    public function show($id)
    {
        return $this->success($this->saleService->getSaleById($id));
    }

    public function store(Request $request)
    {
        $sale = $this->saleService->createSale($request->all());
        return $this->success($sale, 201);
    }

    public function update(Request $request, $id)
    {
        $sale = $this->saleService->updateSale($id, $request->all());
        return $this->success($sale);
    }

    public function destroy($id)
    {
        return $this->success($this->saleService->deleteSale($id));
    }
}
