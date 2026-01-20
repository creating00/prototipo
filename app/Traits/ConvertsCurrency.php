<?php

namespace App\Traits;

use App\Services\CurrencyExchangeService;
use App\Enums\CurrencyType;
use Illuminate\Support\Facades\Log;

trait ConvertsCurrency
{
    public function scopeSumConverted($query)
    {
        return static::sumWithCurrencyConversion($query);
    }

    protected static function sumWithCurrencyConversion($query)
    {
        $expenses = $query->get(['amount', 'currency']);

        if ($expenses->isEmpty()) {
            Log::debug('No expenses found, returning 0');
            return 0;
        }

        $currencyService = app(CurrencyExchangeService::class);
        $rate = $currencyService->getCurrentDollarRate();

        $totalArs = 0;

        foreach ($expenses as $index => $expense) {

            if ($expense->currency === CurrencyType::ARS) {
                $totalArs += (float) $expense->amount;
            } else {
                $converted = (float) $expense->amount * $rate;
                $totalArs += $converted;
            }
        }

        return $totalArs;
    }

    public function scopeSumConvertedWithBreakdown($query)
    {
        $expenses = $query->get(['amount', 'currency']);

        if ($expenses->isEmpty()) {
            return ['ars' => 0, 'usd' => 0, 'rate' => 0];
        }

        $currencyService = app(CurrencyExchangeService::class);
        $rate = $currencyService->getCurrentDollarRate();
        $totalArs = 0;
        $totalUsd = 0;

        foreach ($expenses as $expense) {
            if ($expense->currency === CurrencyType::ARS) {
                $totalArs += (float) $expense->amount;
                $totalUsd += $rate > 0 ? (float) $expense->amount / $rate : 0;
            } else {
                $totalUsd += (float) $expense->amount;
                $totalArs += (float) $expense->amount * $rate;
            }
        }

        return [
            'ars' => $totalArs,
            'usd' => $totalUsd,
            'rate' => $rate,
        ];
    }
}
