<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CurrencyExchangeService;

class CurrencyController extends Controller
{
    public function rate(Request $request, CurrencyExchangeService $service)
    {
        $type = $request->query('type', 'venta');

        return response()->json([
            'rate' => $service->getCurrentDollarRate($type),
            'type' => $type,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
