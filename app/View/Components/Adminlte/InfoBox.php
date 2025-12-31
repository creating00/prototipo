<?php

namespace App\View\Components\Adminlte;

use Illuminate\View\Component;

class InfoBox extends Component
{
    public string $icon;
    public string $color;
    public string $text;

    public string|int $number;
    public ?string $prefix;
    public ?string $suffix;

    public string|int|null $secondaryNumber;
    public ?string $secondarySuffix;

    public function __construct(
        string $icon,
        string $color = 'primary',
        string $text = '',
        string|int $number = 0,
        ?string $prefix = null,
        ?string $suffix = null,
        string|int|null $secondaryNumber = null,
        ?string $secondarySuffix = null,
    ) {
        $this->icon = $icon;
        $this->color = $color;
        $this->text = $text;
        $this->number = $number;
        $this->prefix = $prefix;
        $this->suffix = $suffix;
        $this->secondaryNumber = $secondaryNumber;
        $this->secondarySuffix = $secondarySuffix;
    }

    public function render()
    {
        return view('components.adminlte.infobox');
    }
}
