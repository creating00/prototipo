<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'title',
        'subtitle',
        'label',
        'buttons',
        'order',
        'is_active',
    ];

    protected $casts = [
        'buttons' => 'array',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('order', 'asc');
    }
}
