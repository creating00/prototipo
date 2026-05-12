<?php

namespace App\View\Components\Adminlte;

use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class MenuBuilder extends Component
{
    public $items;

    public function __construct($items = null)
    {
        $raw = $items ?? config('admin-side-menu.items', []);
        $this->items = $this->processItems($raw);
    }

    private function processItems(array $items)
    {
        $user = Auth::user();
        $pendingOrdersCount = 0;

        if ($user instanceof \App\Models\User && !is_null($user->branch_id)) {

            // Eloquent ya aplica el filtro de Soft Deletes por defecto.
            // Solo registros con deleted_at IS NULL serán contados.
            $pendingOrdersCount = \App\Models\Order::where('branch_id', $user->branch_id)
                ->where('status', \App\Enums\OrderStatus::Pending)
                ->count();
        }

        foreach ($items as &$item) {
            // Resolver Href
            if (isset($item['href'])) {
                $item['href'] = $this->resolveHref($item['href']);

                // Si es el Registro de Pedidos, inyectamos el conteo real de la DB
                if ($item['href'] === route('web.orders.index') && $pendingOrdersCount > 0) {
                    $item['badge'] = [
                        'value' => $pendingOrdersCount,
                        'class' => 'badge text-bg-danger'
                    ];
                }
            }

            // Recursividad para subitems
            if (isset($item['subitems']) && is_array($item['subitems'])) {
                $item['subitems'] = $this->processItems($item['subitems']);
            }

            // Recursividad para children (nested)
            if (isset($item['children']) && is_array($item['children'])) {
                $item['children'] = $this->processItems($item['children']);
            }
        }

        return $items;
    }

    private function resolveHref($href)
    {
        if (Str::startsWith($href, ['http://', 'https://', '/', '#'])) {
            return $href;
        }
        return route($href);
    }

    public function render()
    {
        return view('components.adminlte.menu-builder');
    }
}
