<?php

namespace App\View\Components\Adminlte;

use Illuminate\View\Component;

class SubMenuItem extends Component
{
    public $href;
    public $icon;
    public $label;
    public $active;
    public $color;

    public function __construct($href = '#', $icon = 'bi bi-circle', $label = '', $active = false, $color = '')
    {
        $this->href = $href;
        $this->icon = $icon;
        $this->label = $label;
        $this->active = $active;
        $this->color = $color;
    }

    public function render()
    {
        return view('components.adminlte.sub-menu-item');
    }
}
