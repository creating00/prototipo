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
        'branch_id',
        'document',
        'full_name',
        'phone',
        'address',
        'email',
        'is_system'
    ];

    protected $appends = ['display_name'];

    public function getDisplayNameAttribute(): string
    {
        return "[{$this->document}] {$this->full_name} - {$this->email}";
    }

    // Para tablas, con HTML
    public function getDisplayNameHtmlAttribute(): string
    {
        // Solo ponemos el documento en negritas
        return "<strong>[{$this->document}]</strong> {$this->full_name}";
    }

    // Método helper opcional para decidir formato según contexto
    public function displayName(bool $html = false): string
    {
        return $html ? $this->display_name_html : $this->display_name;
    }

    public function orders()
    {
        return $this->morphMany(Order::class, 'customer');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where($this->getTable() . '.branch_id', $branchId);
    }
}
