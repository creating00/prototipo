<?php
// app/View/Components/AdminLte/ContentHeader.php

namespace App\View\Components\AdminLte;

use Illuminate\View\Component;

class ContentHeader extends Component
{
    public $title;
    public $breadcrumbs;

    public function __construct($title = 'Dashboard', $breadcrumbs = null)
    {
        $this->title = $title;
        $this->breadcrumbs = $breadcrumbs ?? [
            ['label' => 'Home', 'url' => '#'],
            ['label' => $title, 'url' => null, 'active' => true]
        ];
    }

    public function render()
    {
        return view('components.admin-lte.content-header', [
            'title' => $this->title,
            'breadcrumbs' => $this->breadcrumbs
        ]);
    }
}
