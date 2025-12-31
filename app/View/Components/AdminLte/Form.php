<?php

namespace App\View\Components\Adminlte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Form extends Component
{
    public string $id;
    public string $submitText;

    /**
     * @param string|null $action URL de destino del formulario
     * @param string $method Método HTTP (POST, PUT, DELETE, etc.)
     * @param string|null $title Título de la Card
     * @param string|null $submitText Texto del botón principal
     * @param string $submittingText Texto mostrado al procesar
     * @param string|null $id ID del formulario (para vinculación externa)
     * @param bool|null $showFooter Fuerza la visibilidad del footer
     */
    public function __construct(
        public ?string $action = null,
        public string $method = 'POST',
        public ?string $title = null,
        ?string $submitText = null,
        public string $submittingText = 'Guardando...',
        ?string $id = null,
        public ?bool $showFooter = null
    ) {
        $this->id = $id ?? 'form_' . bin2hex(random_bytes(4));
        $this->submitText = $submitText ?? $this->getDefaultSubmitText();
    }

    private function getDefaultSubmitText(): string
    {
        return match (strtoupper($this->method)) {
            'POST'         => 'Crear',
            'PUT', 'PATCH' => 'Actualizar',
            'DELETE'       => 'Eliminar',
            default        => 'Enviar',
        };
    }

    public function render(): View|Closure|string
    {
        return view('components.adminlte.form');
    }
}
