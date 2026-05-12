<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PromotionImage extends Model
{
    protected $fillable = [
        'branch_id',
        'image_path',
        'is_active',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ends_at' => 'datetime',
    ];

    /**
     * Relación con la sucursal.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
