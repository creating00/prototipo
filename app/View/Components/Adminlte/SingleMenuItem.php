<?php

namespace App\View\Components\Adminlte;

use Illuminate\View\Component;

class SingleMenuItem extends Component
{
    // Definir propiedades para que estén disponibles en la vista
    public $href;
    public $icon;
    public $label;
    public $active;
    public $color;

    public function __construct($href = '#', $icon = '', $label = '', $active = false, $color = '')
    {
        $this->href = $href;
        $this->icon = $icon;
        $this->label = $label;
        $this->active = $active;
        $this->color = $color;
    }

    public function render()
    {
        return view('components.adminlte.single-menu-item');
    }
}
