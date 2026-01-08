<?php

namespace App\View\Components\Bootstrap;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Select extends Component
{
    public $name;
    public $label;
    public $options;
    public $selected;
    public $required;
    public $placeholder;

    public function __construct($name, $options = [], $label = null, $selected = null, $required = false, $placeholder = 'Seleccione una opción')
    {
        $this->name = $name;
        $this->options = $options;
        $this->label = $label;
        $this->selected = $selected;
        $this->required = $required;
        $this->placeholder = $placeholder;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.bootstrap.select');
    }
}
