<?php

namespace App\View\Components\AdminLte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AlertManager extends Component
{
    public $autoClose;
    public $dismissible;
    public $showValidationErrors;

    public function __construct(
        $autoClose = true,
        $dismissible = true,
        $showValidationErrors = true
    ) {
        $this->autoClose = $autoClose;
        $this->dismissible = $dismissible;
        $this->showValidationErrors = $showValidationErrors;
    }

    public function render(): View|Closure|string
    {
        return view('components.admin-lte.alert-manager');
    }
}
