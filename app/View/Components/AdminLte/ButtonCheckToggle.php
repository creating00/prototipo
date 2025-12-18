<?php

namespace App\View\Components\AdminLte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ButtonCheckToggle extends Component
{
    /**
     * @param string $id ID único para el input y el atributo 'for' del label.
     * @param string $color Color del botón (e.g., primary, secondary).
     * @param bool $checked Estado inicial marcado.
     */
    public function __construct(
        public string $id,
        public string $color = 'primary',
        public bool $checked = false,
        public string $class = ''
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.admin-lte.button-check-toggle');
    }
}
