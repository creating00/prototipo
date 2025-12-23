<?php

namespace App\View\Components\Adminlte;

use Illuminate\Support\Str;
use Illuminate\View\Component;

class MenuBuilder extends Component
{
    public $items;

    public function __construct($items = null)
    {
        $raw = $items ?? config('admin-side-menu.items', []);

        // Procesar recursivamente
        $this->items = $this->processItems($raw);
    }

    private function processItems(array $items)
    {
        foreach ($items as &$item) {

            // Si el item tiene un href => lo convertimos
            if (isset($item['href'])) {
                $item['href'] = $this->resolveHref($item['href']);
            }

            // Si el item tiene subitems => procesarlos
            if (isset($item['subitems']) && is_array($item['subitems'])) {
                $item['subitems'] = $this->processItems($item['subitems']);
            }

            // Children (nested)
            if (isset($item['children']) && is_array($item['children'])) {
                $item['children'] = $this->processItems($item['children']);
            }
        }

        return $items;
    }

    private function resolveHref($href)
    {
        // URL absoluta o relativa => mantener
        if (Str::startsWith($href, ['http://', 'https://', '/', '#'])) {
            return $href;
        }

        // Nombre de ruta => convertir
        return route($href);
    }

    public function render()
    {
        return view('components.adminlte.menu-builder');
    }
}
