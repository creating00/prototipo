<?php

namespace App\View\Components\AdminLte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Form extends Component
{
    public $action;
    public $method;
    public $title;
    public $submitText;
    public $submittingText;

    public function __construct(
        $action = null,
        $method = 'POST',
        $title = null,
        $submitText = null,
        $submittingText = null
    ) {
        $this->action = $action;
        $this->method = $method;
        $this->title = $title;
        $this->submitText = $submitText ?? $this->getDefaultSubmitText();
        $this->submittingText = $submittingText ?? 'Guardando...';
    }

    private function getDefaultSubmitText()
    {
        // Determinar texto basado en el método HTTP
        return match (strtoupper($this->method)) {
            'POST' => 'Crear',
            'PUT', 'PATCH' => 'Actualizar',
            'DELETE' => 'Eliminar',
            default => 'Enviar'
        };
    }

    public function render(): View|Closure|string
    {
        return view('components.admin-lte.form');
    }
}
