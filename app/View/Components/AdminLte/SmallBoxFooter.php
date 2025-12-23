<?php

namespace App\View\Components\Adminlte;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Closure;

class SmallBoxFooter extends Component
{
    public function __construct(public string $url = '#') {}

    public function render(): View|Closure|string
    {
        return view('components.adminlte.small-box-footer');
    }
}
