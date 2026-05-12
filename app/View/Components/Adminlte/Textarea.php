<?php

namespace App\View\Components\Adminlte;

use Illuminate\View\Component;

class Textarea extends Component
{
    public $id;
    public $name;
    public $label;
    public $value;
    public $required;
    public $rows;
    public $placeholder;

    public function __construct(
        $id,
        $name,
        $label,
        $value = null,
        $required = false,
        $rows = 3,
        $placeholder = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->label = $label;
        $this->value = $value;
        $this->required = $required;
        $this->rows = $rows;
        $this->placeholder = $placeholder ?? $label;
    }

    public function render()
    {
        return view('components.adminlte.textarea');
    }
}
