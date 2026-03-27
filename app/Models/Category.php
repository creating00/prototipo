<?php

namespace App\Models;

use App\Enums\CategoryTarget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'is_system',
        'target',
    ];

    protected $casts = [
        'target' => \App\Enums\CategoryTarget::class,
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Filtra categorías que no tengan un target específico.
     */
    public function scopeExceptTarget(Builder $query, CategoryTarget $target): Builder
    {
        return $query->where('target', '!=', $target->value);
    }
}
