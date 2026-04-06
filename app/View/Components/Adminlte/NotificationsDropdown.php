<?php

namespace App\View\Components\Adminlte;

use App\Models\User;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;

class NotificationsDropdown extends Component
{
    public function __construct()
    {
        //
    }

    public function render(): View|Closure|string
    {
        $user = Auth::user();

        // Validar que el usuario exista y sea instancia del modelo correcto
        if (!$user instanceof User) {
            return view('components.adminlte.notifications-dropdown', [
                'unreadCount' => 0,
                'notifications' => collect()
            ]);
        }

        $unreadCount = $user->unreadNotifications()->count();
        $notifications = $user->unreadNotifications()->take(5)->get();

        return view('components.adminlte.notifications-dropdown', compact('unreadCount', 'notifications'));
    }
}
