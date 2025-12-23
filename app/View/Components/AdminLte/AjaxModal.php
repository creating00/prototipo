<?php

namespace App\View\Components\AdminLte;

use Illuminate\View\Component;

class AjaxModal extends Component
{
    public string $modalId;
    public string $title;
    public string $formId;
    public string $btnSaveId;
    public string $submitUrl;
    public bool $large;

    public function __construct(
        string $modalId,
        string $title,
        string $formId,
        string $btnSaveId,
        string $submitUrl = '#',
        bool $large = true
    ) {
        $this->modalId = $modalId;
        $this->title = $title;
        $this->formId = $formId;
        $this->btnSaveId = $btnSaveId;
        $this->submitUrl = $submitUrl;
        $this->large = $large;
    }

    public function render()
    {
        return view('components.admin-lte.ajax-modal');
    }
}
