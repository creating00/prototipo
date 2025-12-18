<?php

namespace App\View\Components\AdminLte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SelectWithAction extends Component
{
    public $name;
    public $label;
    public $options;
    public $value;
    public $placeholder;
    public $required;
    public $searchEnabled;
    public $multiple;
    public $buttonColor;
    public $buttonIcon;
    public $buttonLabel;
    public $buttonTitle;
    public $buttonClass;
    public $buttonId;          // <-- NUEVO
    public $showLabel;

    public function __construct(
        $name,
        $label = null,
        $options = [],
        $value = null,
        $placeholder = null,
        $required = false,
        $searchEnabled = true,
        $multiple = false,
        $buttonColor = 'custom-electric',
        $buttonIcon = 'fas fa-plus',
        $buttonLabel = 'Nueva',
        $buttonTitle = null,
        $buttonClass = 'px-2 py-1',
        $buttonId = null,       // <-- NUEVO
        $showLabel = true
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->options = $options;
        $this->value = $value;
        $this->placeholder = $placeholder;
        $this->required = (bool) $required;
        $this->searchEnabled = (bool) $searchEnabled;
        $this->multiple = (bool) $multiple;
        $this->buttonColor = $buttonColor;
        $this->buttonIcon = $buttonIcon;
        $this->buttonLabel = $buttonLabel;
        $this->buttonTitle = $buttonTitle ?? "Agregar {$label}";
        $this->buttonClass = $buttonClass;
        $this->buttonId = $buttonId;   // <-- NUEVO
        $this->showLabel = (bool) $showLabel;
    }

    public function render(): View|Closure|string
    {
        return view('components.admin-lte.select-with-action');
    }
}
