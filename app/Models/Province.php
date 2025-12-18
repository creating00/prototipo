<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Province extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_id',
        'name',
        'name_long',
    ];

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }
}
