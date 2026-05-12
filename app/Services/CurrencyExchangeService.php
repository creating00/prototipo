<?php
// app/Services/CurrencyExchangeService.php
namespace App\Services;

use App\Enums\CurrencyType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyExchangeService
{
    private const DOLLAR_API_URL = 'https://dolarapi.com/v1/dolares/blue';
    private const CACHE_KEY = 'current_dollar_rate';
    private const CACHE_TTL = 300; // 5 minutos en segundos

    public static function getCacheKey(): string
    {
        return self::CACHE_KEY;
    }

    public function getCurrentDollarRate(string $type = 'venta'): float
    {
        return Cache::remember(self::CACHE_KEY . '_' . $type, self::CACHE_TTL, function () use ($type) {
            try {
                $response = Http::timeout(5)->get(self::DOLLAR_API_URL);

                if ($response->successful()) {
                    $data = $response->json();

                    // Elegir según tipo solicitado
                    $rate = match ($type) {
                        'venta' => $data['venta'] ?? 0,
                        'compra' => $data['compra'] ?? 0,
                        'promedio' => (($data['venta'] ?? 0) + ($data['compra'] ?? 0)) / 2,
                        default => $data['venta'] ?? 0,
                    };

                    return $rate ?: $this->getFallbackRate();
                }
            } catch (\Exception $e) {
                Log::error('Error fetching dollar rate: ' . $e->getMessage());
            }

            return $this->getFallbackRate();
        });
    }

    public function convertToArs(float $amount, CurrencyType $fromCurrency): float
    {
        if ($fromCurrency === CurrencyType::ARS) {
            return $amount;
        }

        return $amount * $this->getCurrentDollarRate();
    }

    public function convertToUsd(float $amount, CurrencyType $fromCurrency): float
    {
        if ($fromCurrency === CurrencyType::USD) {
            return $amount;
        }

        $rate = $this->getCurrentDollarRate();
        return $rate > 0 ? $amount / $rate : 0;
    }

    private function getFallbackRate(): float
    {
        return config('app.fallback_dollar_rate', 1000);
    }
}
