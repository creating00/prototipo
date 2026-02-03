<?php

namespace App\Traits;

use App\Services\CurrencyExchangeService;
use App\Enums\CurrencyType;

trait ConvertsCurrency
{
    /**
     * Suma convertida directamente en Base de Datos (Eficiente)
     */
    public function scopeSumConverted($query)
    {
        $rate = app(CurrencyExchangeService::class)->getCurrentDollarRate();

        return (float) $query->selectRaw("
            SUM(CASE 
                WHEN currency = ? THEN amount * ? 
                ELSE amount 
            END) as total", [CurrencyType::USD->value, $rate])
            ->value('total') ?? 0;
    }

    /**
     * Breakdown de totales sin cargar modelos en memoria
     */
    public function scopeSumConvertedWithBreakdown($query)
    {
        $rate = app(CurrencyExchangeService::class)->getCurrentDollarRate();

        $totals = $query->selectRaw("
            SUM(CASE WHEN currency = ? THEN amount ELSE 0 END) as ars_sum,
            SUM(CASE WHEN currency = ? THEN amount ELSE 0 END) as usd_sum
        ", [CurrencyType::ARS->value, CurrencyType::USD->value])
            ->first();

        $ars = (float) ($totals->ars_sum ?? 0);
        $usd = (float) ($totals->usd_sum ?? 0);

        return [
            'ars'  => $ars + ($usd * $rate),
            'usd'  => $usd + ($rate > 0 ? $ars / $rate : 0),
            'rate' => $rate,
        ];
    }
}
