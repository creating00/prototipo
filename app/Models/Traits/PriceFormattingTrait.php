<?php

namespace App\Models\Traits;

trait PriceFormattingTrait
{
    /**
     * Devuelve el precio de VENTA (minorista) formateado con el símbolo de la moneda.
     */
    public function getSalePriceForDisplay(): string
    {
        return $this->formatPriceInternal($this->sale_price);
    }

    /**
     * Devuelve el precio de COMPRA formateado con el símbolo de la moneda.
     */
    public function getPurchasePriceForDisplay(): string
    {
        return $this->formatPriceInternal($this->purchase_price);
    }

    /**
     * Devuelve el precio MAYORISTA formateado con el símbolo de la moneda, o null si es nulo.
     */
    public function getWholesalePriceForDisplay(): ?string
    {
        if (is_null($this->wholesale_price)) {
            return null;
        }
        return $this->formatPriceInternal($this->wholesale_price);
    }

    /**
     * Lógica interna para formatear cualquier valor de precio.
     * @param float $price
     * @return string
     */
    private function formatPriceInternal(float $price): string
    {
        // $this->currency es una instancia de App\Enums\CurrencyType gracias al casting en el modelo Product.
        $symbol = $this->currency->symbol();

        // Formatear el precio: 2 decimales, coma (,) como separador decimal, punto (.) como separador de miles.
        $formattedPrice = number_format($price, 2, ',', '.');

        // Unir símbolo y precio
        return "{$symbol} {$formattedPrice}";
    }
}
