<?php

namespace App\View\Components\Adminlte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ButtonRadioToggle extends Component
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $color;

    /**
     * @var bool
     */
    public $checked;

    /**
     * @var string|null 
     */
    public $value;

    /**
     * Create a new component instance.
     *
     * @param string $id Identificador único del input y for del label.
     * @param string $name Nombre del grupo de radio.
     * @param string $color Color de Bootstrap (ej: 'info', 'primary').
     * @param bool $checked Indica si debe estar seleccionado por defecto.
     */
    public function __construct(string $id, string $name, string $color = 'primary', bool $checked = false, $value = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->color = $color;
        $this->checked = $checked;
        $this->value = $value ?? $id;
    }

    // ... (El resto de la clase permanece igual)
    public function render(): View|Closure|string
    {
        return view('components.adminlte.button-radio-toggle');
    }
}
