<?php

namespace App\View\Components\AdminLte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ToastContainer extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $position = 'bottom-0 end-0',
        public string $class = 'p-3'
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.admin-lte.toast-container');
    }
}
