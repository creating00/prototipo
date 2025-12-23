<?php
// app/Traits/AuthTrait.php
namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait AuthTrait
{
    protected function currentUser()
    {
        return Auth::user();
    }

    protected function currentBranchId(): ?int
    {
        // Forzamos Auth::user() y verificamos el atributo
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        // Si el id viene como string de la DB, nos aseguramos que sea int
        return $user->branch_id ? (int) $user->branch_id : null;
    }

    protected function userId(): ?int
    {
        return Auth::id();
    }
}
