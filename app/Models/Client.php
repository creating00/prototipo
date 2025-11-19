<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes
};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'document',
        'first_name',
        'last_name',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
