<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ClientAccount extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'client_accounts';

    protected $fillable = [
        'client_id',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
