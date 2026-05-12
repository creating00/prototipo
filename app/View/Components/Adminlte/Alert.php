<?php

namespace App\View\Components\Adminlte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Alert extends Component
{
    public $type;
    public $dismissible;
    public $autoClose;
    public $message;
    public $link;
    public $linkText;

    public function __construct(
        $type = 'info',
        $dismissible = true,
        $autoClose = 4000,
        $message = null,
        $link = null,
        $linkText = null
    ) {
        $this->type = $type;
        $this->dismissible = $dismissible;
        $this->autoClose = $autoClose;
        $this->message = $message;
        $this->link = $link;
        $this->linkText = $linkText;
    }

    public function render(): View|Closure|string
    {
        return view('components.adminlte.alert');
    }
}
