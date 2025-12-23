<?php

namespace App\View\Components\Bootstrap;

use Illuminate\View\Component;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CompactTextArea extends Component
{
    public string $id;
    public string $name;
    public string $label;
    public ?string $value;
    public bool $required;
    public bool $disabled;
    public bool $readonly;
    public ?string $placeholder;
    public ?string $helpText;
    public string $errorKey;
    public int $rows;
    public ?int $maxlength;
    public string $wrapperClass;
    public string $resizeClass;
    public string $validationClass;

    public function __construct(
        ?string $id = null,
        string $name,
        string $label,
        ?string $value = null,
        bool $required = false,
        bool $disabled = false,
        bool $readonly = false,
        ?string $placeholder = null,
        ?string $helpText = null,
        ?string $errorKey = null,
        int $rows = 4,
        ?int $maxlength = null,
        string $wrapperClass = '',
        bool|string $resize = true
    ) {
        $this->id = $id ?? 'textarea_' . Str::random(10);
        $this->name = $name;
        $this->label = $label;
        $this->value = old($name, $value);
        $this->required = $required;
        $this->disabled = $disabled;
        $this->readonly = $readonly;
        $this->placeholder = $placeholder;
        $this->helpText = $helpText;
        $this->errorKey = $errorKey ?? $name;
        $this->rows = $rows;
        $this->maxlength = $maxlength;
        $this->wrapperClass = $wrapperClass;

        $this->resizeClass = match ($resize) {
            false, 'none' => 'resize-none',
            'horizontal' => 'resize-horizontal',
            'vertical' => 'resize-vertical',
            default => '',
        };

        $this->validationClass = session('errors')?->has($this->errorKey)
            ? 'is-invalid'
            : '';
    }

    public function render(): View
    {
        return view('components.bootstrap.compact-text-area');
    }
}
