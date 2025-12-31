<?php

namespace App\View\Components\Adminlte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DynamicModal extends Component
{
    public string $modalId;
    public string $title;
    public string $formId;
    public string $btnSaveId;
    public ?string $route;
    public string $selectId;
    public bool $refreshOnSave;
    public ?string $refreshUrl;
    public ?string $formView;
    public array $formData;
    public bool $localSubmit;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $modalId,
        string $title,
        string $formId,
        string $btnSaveId,
        ?string $route = null,
        string $selectId = '',
        bool $refreshOnSave = false,
        ?string $refreshUrl = null,
        ?string $formView = null,
        array $formData = [],
        bool $localSubmit = false
    ) {
        $this->modalId = $modalId;
        $this->title = $title;
        $this->formId = $formId;
        $this->btnSaveId = $btnSaveId;
        $this->route = $route;
        $this->selectId = $selectId;
        $this->refreshOnSave = $refreshOnSave;
        $this->refreshUrl = $refreshUrl;
        $this->formView = $formView;
        $this->formData = $formData;
        $this->localSubmit = $localSubmit;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.adminlte.dynamic-modal');
    }
}
