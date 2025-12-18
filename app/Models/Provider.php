<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_name',
        'tax_id',
        'short_name',
        'contact_name',
        'email',
        'phone',
        'address',
    ];
}
