<?php

namespace App\Models\Concerns;

use App\Enums\CurrencyType;

trait HasCurrency
{
    protected function initializeHasCurrency(): void
    {
        if (! isset($this->casts['currency'])) {
            $this->casts['currency'] = CurrencyType::class;
        }
    }

    public function isUSD(): bool
    {
        return $this->currency === CurrencyType::USD;
    }

    public function isARS(): bool
    {
        return $this->currency === CurrencyType::ARS;
    }

    public function getFormattedTotalAttribute(): string
    {
        if (!isset($this->total_amount)) {
            return '';
        }

        return sprintf(
            '%s %s',
            $this->currency->symbol(),
            number_format($this->total_amount, 2, ',', '.')
        );
    }

    public function getCurrencyCode(): string
    {
        return $this->currency->value;
    }
}
