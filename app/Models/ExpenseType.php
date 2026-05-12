<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    // Para que siempre se incluya en JSON
    protected $appends = ['display_name'];

    /**
     * Accessor: devuelve un nombre compuesto para mostrar en selects.
     */
    public function getDisplayNameAttribute(): string
    {
        // Si hay descripción, la concatenamos
        if ($this->description) {
            return "{$this->name} - {$this->description}";
        }

        // Si no hay descripción, solo el nombre
        return $this->name;
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
