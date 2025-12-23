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

    public function __construct($title = null, $type = 'primary', $footer = null)
    {
        $this->title = $title;
        $this->type = $type;
        $this->footer = $footer;
    }

    public function render(): View|Closure|string
    {
        return view('components.adminlte.card');
    }
}
