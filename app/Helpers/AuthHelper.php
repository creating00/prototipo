<?php
// app/Helpers/AuthHelper.php
namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class AuthHelper
{
    public static function currentUser()
    {
        return Auth::user();
    }
    
    public static function currentBranchId()
    {
        return Auth::user()?->branch_id;
    }
}