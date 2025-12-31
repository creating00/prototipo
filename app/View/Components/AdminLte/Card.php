<?php

namespace App\View\Components\Adminlte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Card extends Component
{
    public $title;
    public $type;
    public $footer;
    public $showTools; // Nueva propiedad

    public function __construct(
        $title = null,
        $type = 'primary',
        $footer = null,
        $showTools = false // Por defecto oculto
    ) {
        $this->title = $title;
        $this->type = $type;
        $this->footer = $footer;
        $this->showTools = $showTools;
    }

    public function render(): View|Closure|string
    {
        return view('components.adminlte.card');
    }
}
