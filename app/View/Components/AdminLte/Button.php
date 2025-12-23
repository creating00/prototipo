<?php

namespace App\View\Components\Adminlte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Button extends Component
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
        'link',
        'white'
    ];

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $type = 'button',
        public string $color = 'primary',
        public ?string $size = null,
        public bool $outline = false,
        public bool $disabled = false,
        public string $class = '',
        public ?string $icon = null,
        public string $iconPosition = 'left',
        public bool $customColor = false
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.adminlte.button', [
            'allowedColors' => $this->allowedColors
        ]);
    }
}
