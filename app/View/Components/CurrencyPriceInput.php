<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CurrencyPriceInput extends Component
{
    /**
     * El nombre del campo del input de monto (ej: 'purchase_price').
     * @var string
     */
    public $name;

    /**
     * La etiqueta que se mostrará sobre el input.
     * @var string
     */
    public $label;

    /**
     * El valor actual/por defecto del monto.
     * @var mixed
     */
    public $amountValue;

    /**
     * El valor actual/por defecto de la moneda.
     * @var mixed
     */
    public $currencyValue;

    /**
     * Las opciones del select de moneda (ej: [1 => 'ARS', 2 => 'USD']).
     * @var array
     */
    public $currencyOptions;

    /**
     * Define si los inputs del componente son obligatorios.
     * @var bool
     */
    public $required;

    /**
     * Define si los inputs están deshabilitados/solo lectura.
     * @var bool
     */
    public $disabled;

    public function __construct(
        string $name,
        string $label,
        $amountValue = null,
        $currencyValue = 1, // Default currency if not provided
        array $currencyOptions = [],
        bool $required = true,
        ?bool $disabled = null
    ) {
        $this->name = $name;
        // Permite la inyección de valores antiguos de Laravel (old input helper)
        $this->amountValue = old($name . '_amount', $amountValue);
        $this->currencyValue = old($name . '_currency', $currencyValue);

        $this->label = $label;
        $this->currencyOptions = $currencyOptions;
        $this->required = $required;

        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $isProvincialAdmin = $user && $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value);
        $this->disabled = $disabled !== null ? (bool) $disabled : !$isProvincialAdmin;
    }

    /**
     * Obtiene la vista / contenidos que representan el componente.
     */
    public function render(): View|Closure|string
    {
        return view('components.currency-price-input');
    }
}
