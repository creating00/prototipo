<?php
// app/View/Components/AdminLte/AppMain.php

namespace App\View\Components\AdminLte;

use Illuminate\View\Component;
use Illuminate\Support\Facades\View;

class AppMain extends Component
{
    public $pageTitle;
    public $breadcrumbs;

    public function __construct($pageTitle = null)
    {
        // Obtener el título de la página de la sección o usar el valor por defecto
        $this->pageTitle = $pageTitle ?? View::getSection('page-title', 'Dashboard');

        // Breadcrumbs automáticos
        $this->breadcrumbs = [
            ['label' => 'Home', 'url' => '#'],
            ['label' => $this->pageTitle, 'active' => true]
        ];
    }

    public function render()
    {
        return view('components.admin-lte.app-main');
    }
}
