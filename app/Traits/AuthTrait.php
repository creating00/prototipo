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
        return Auth::user()?->branch_id;
    }
    
    protected function userId(): ?int
    {
        return Auth::id();
    }
}