<?php

namespace App\View\Components\AdminLte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class InputGroupText extends Component
{
    public string $id;
    public string $name;
    public ?string $label;
    public string $type;
    public ?string $prependText;
    public ?string $appendText;
    public ?string $value;
    public bool $required;
    public bool $autofocus;

    public function __construct(
        string $id,
        string $name,
        ?string $label = null,
        string $type = 'text',
        ?string $prependText = null,
        ?string $appendText = null,
        ?string $value = null,
        bool $required = false,
        bool $autofocus = false
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->label = $label;
        $this->type = $type;
        $this->prependText = $prependText;
        $this->appendText = $appendText;
        $this->value = $value;
        $this->required = $required;
        $this->autofocus = $autofocus;
    }

    public function render(): View|Closure|string
    {
        return view('components.admin-lte.input-group-text');
    }
}
