<?php

namespace App\View\Components\Adminlte;

use Illuminate\View\Component;

class FormFooter extends Component
{
    public string $cancelRoute;
    public string $submitText;

    public function __construct(
        string $cancelRoute,
        string $submitText = 'Guardar'
    ) {
        $this->cancelRoute = $cancelRoute;
        $this->submitText = $submitText;
    }

    public function render()
    {
        return view('components.adminlte.form-footer');
    }
}
