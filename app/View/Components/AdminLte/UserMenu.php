<?php
// app/View/Components/AdminLte/UserMenu.php

namespace App\View\Components\AdminLte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;

class UserMenu extends Component
{
    public $user;
    public $userImage;
    public $userRole;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->user = Auth::user();
        $this->userImage = $this->getUserImage();
        $this->userRole = $this->getUserRole();
    }

    /**
     * Get user image - prioriza imagen personalizada, sino usa default
     */
    protected function getUserImage()
    {
        // Si el usuario tiene una imagen en la base de datos
        if ($this->user && $this->user->profile_photo_path) {
            return asset('storage/' . $this->user->profile_photo_path);
        }

        // Si usa Gravatar o similar (puedes implementarlo)
        // if ($this->user && $this->user->email) {
        //     return $this->getGravatar($this->user->email);
        // }

        // Imagen por defecto
        return asset('assets/img/user.webp');
    }

    /**
     * Get user role/position
     */
    protected function getUserRole()
    {
        // Si tienes un campo específico en el modelo User
        if ($this->user && $this->user->role) {
            return $this->user->role;
        }

        // O si quieres usar un valor por defecto basado en email/name
        return 'Web Developer'; // Puedes personalizar esto
    }

    /**
     * Opcional: Método para Gravatar
     */
    protected function getGravatar($email, $size = 128)
    {
        $hash = md5(strtolower(trim($email)));
        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mp";
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.admin-lte.user-menu');
    }
}
