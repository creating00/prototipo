<?php

namespace App\View\Components\Adminlte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Toast extends Component
{
    public array $allowedColors = [
        'primary',
        'secondary',
        'success',
        'danger',
        'warning',
        'info',
        'light',
        'dark',
    ];

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $id,
        public ?string $color = null,
        public ?string $title = null,
        public ?string $time = '11 mins ago',
        public ?string $icon = 'bi bi-circle',
        public bool $autohide = true,
        public int $delay = 5000,
        public string $class = ''
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.adminlte.toast');
    }
}
