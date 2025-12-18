<?php

namespace App\View\Components\AdminLte;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Closure;

class SmallBoxIcon extends Component
{
    public string $svgViewBox;
    public string $svgContent;
    public bool $useFullPath;

    public function __construct(
        string $svgViewBox = '0 0 24 24',
        string $svgContent = '',
        bool $useFullPath = false
    ) {
        $this->svgViewBox = $svgViewBox;
        $this->svgContent = $svgContent;
        $this->useFullPath = $useFullPath;
    }

    public function render(): View|Closure|string
    {
        return view('components.admin-lte.small-box-icon');
    }
}
