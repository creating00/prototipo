<?php

namespace App\View\Components\Bootstrap;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class CompactInputGroup extends Component
{
    public ?string $id;
    public string $name;
    public string $label;
    public string $type;
    public ?string $placeholder;
    public ?string $value;
    public bool $required;

    public ?string $buttonLabel;
    public ?string $buttonIcon;
    public ?string $buttonOnClick;

    public function __construct(
        ?string $name = null,
        ?string $label = null,
        string $type = 'text',
        string $id = null,
        string $placeholder = null,
        string $value = null,
        bool $required = false,
        string $buttonLabel = null,
        string $buttonIcon = null,
        string $buttonOnClick = null,
    ) {
        if (!$name || !$label) {
            throw new \InvalidArgumentException(
                'compact-input-group requires name and label attributes.'
            );
        }

        $this->id = $id;
        $this->name = $name;
        $this->label = $label;
        $this->type = $type;
        $this->placeholder = $placeholder;
        $this->value = $value;
        $this->required = $required;

        $this->buttonLabel = $buttonLabel;
        $this->buttonIcon = $buttonIcon;
        $this->buttonOnClick = $buttonOnClick;
    }

    public function render(): View
    {
        return view('components.bootstrap.compact-input-group');
    }
}
