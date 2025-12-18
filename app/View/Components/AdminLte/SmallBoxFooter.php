<?php

namespace App\View\Components\AdminLte;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Closure;

class SmallBoxFooter extends Component
{
    public function __construct(public string $url = '#') {}

    public function render(): View|Closure|string
    {
        return view('components.admin-lte.small-box-footer');
    }
}
