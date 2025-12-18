<?php

namespace App\View\Components\AdminLte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class InputGroupDouble extends Component
{
    public string $firstId;
    public string $firstName;
    public ?string $firstLabel;
    public ?string $firstValue;
    public string $secondId;
    public string $secondName;
    public ?string $secondLabel;
    public ?string $secondValue;
    public string $separator;

    public function __construct(
        string $firstId,
        string $firstName,
        ?string $firstLabel = null,
        ?string $firstValue = null,
        string $secondId,
        string $secondName,
        ?string $secondLabel = null,
        ?string $secondValue = null,
        string $separator = '@'
    ) {
        $this->firstId = $firstId;
        $this->firstName = $firstName;
        $this->firstLabel = $firstLabel;
        $this->firstValue = $firstValue;
        $this->secondId = $secondId;
        $this->secondName = $secondName;
        $this->secondLabel = $secondLabel;
        $this->secondValue = $secondValue;
        $this->separator = $separator;
    }

    public function render(): View|Closure|string
    {
        return view('components.admin-lte.input-group-double');
    }
}
