<?php

namespace App\View\Components\AdminLte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class InputGroupWithHelp extends Component
{
    public string $id;
    public string $name;
    public string $label;
    public ?string $prependText;
    public ?string $helpText;
    public string $type;
    public ?string $value;
    public bool $required;

    public function __construct(
        string $id,
        string $name,
        string $label,
        ?string $prependText = null,
        ?string $helpText = null,
        string $type = 'text',
        ?string $value = null,
        bool $required = false
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->label = $label;
        $this->prependText = $prependText;
        $this->helpText = $helpText;
        $this->type = $type;
        $this->value = $value;
        $this->required = $required;
    }

    public function render(): View|Closure|string
    {
        return view('components.admin-lte.input-group-with-help');
    }
}
