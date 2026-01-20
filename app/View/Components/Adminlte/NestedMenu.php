<?php

namespace App\View\Components\Adminlte;

use Illuminate\View\Component;

class NestedMenu extends Component
{
    public $icon;
    public $label;
    public $active;
    public $href;
    public $menuOpen;
    public $color;

    public function __construct($icon = '', $label = '', $active = false, $href = '#', $menuOpen = false, $color = '')
    {
        $this->icon = $icon;
        $this->label = $label;
        $this->active = $active;
        $this->href = $href;
        $this->menuOpen = $menuOpen;
        $this->color = $color;
    }

    public function render()
    {
        return view('components.adminlte.nested-menu');
    }
}
