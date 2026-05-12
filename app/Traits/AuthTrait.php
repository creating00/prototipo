<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Models\User;

trait AuthTrait
{
    protected function currentUser(): ?User
    {
        return Auth::user();
    }

    protected function userId(): ?int
    {
        return Auth::id();
    }

    protected function currentBranchId(): ?int
    {
        return $this->currentUser()?->branch_id
            ? (int) $this->currentUser()->branch_id
            : null;
    }

    protected function redirectIfNotAdmin(string $route)
    {
        if (!$this->currentUser()?->hasRole('admin')) {
            return redirect()->route($route);
        }

        return null;
    }
}
