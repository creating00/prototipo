<?php

namespace App\View\Components\AdminLte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ButtonGroup extends Component
{
    /**
     * Create a new component instance.
     *
     * @param string $label El valor para el atributo aria-label.
     * @param bool $vertical Si es true, usa 'btn-group-vertical'.
     * @param string $class Clases CSS adicionales para el contenedor.
     */
    public function __construct(
        public string $label = 'Button group',
        public bool $vertical = false,
        public string $class = ''
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.admin-lte.button-group');
    }
}
