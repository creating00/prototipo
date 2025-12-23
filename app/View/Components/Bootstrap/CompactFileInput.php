<?php

namespace App\View\Components\Bootstrap;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class CompactFileInput extends Component
{
    public function render(): View
    {
        return view('components.bootstrap.compact-file-input');
    }
}
