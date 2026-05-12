<?php

namespace App\View\Components\Adminlte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ToastTrigger extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $target,
        public string $text = 'Show toast',
        public string $color = 'primary',
        public string $class = 'mb-2'
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.adminlte.toast-trigger');
    }
}
