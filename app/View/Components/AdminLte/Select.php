<?php

namespace App\View\Components\Adminlte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Select extends Component
{
    public $name;
    public $label;
    public $options;
    public $selected;
    public $required;
    public $placeholder;
    public $searchEnabled;
    public $multiple;
    public $value;
    public $showPlaceholder;

    public function __construct(
        $name,
        $label = null,
        $options = [],
        $selected = null,
        $value = null,
        $required = false,
        $placeholder = null,
        $searchEnabled = true,
        $multiple = false,
        $showPlaceholder = true
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->options = $options;
        $this->selected = $value ?? $selected;
        $this->value = $value;
        $this->required = (bool) $required;
        $this->placeholder = $placeholder;
        $this->searchEnabled = (bool) $searchEnabled;
        $this->multiple = (bool) $multiple;
        $this->showPlaceholder = (bool) $showPlaceholder;
    }

    public function render(): View|Closure|string
    {
        return view('components.adminlte.select');
    }
}
