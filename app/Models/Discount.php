<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'value',
        'max_amount',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'type'       => DiscountType::class,
        'is_active'  => 'boolean',
        'value'      => 'decimal:2',
        'max_amount' => 'decimal:2',
    ];

    // =====================
    // Relaciones
    // =====================

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    // =====================
    // Scopes
    // =====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // =====================
    // Lógica de dominio
    // =====================

    public function calculateAmount(float $subtotal): float
    {
        return match ($this->type) {
            DiscountType::Fixed => min(
                $this->value,
                $subtotal
            ),

            DiscountType::Percentage => min(
                ($subtotal * $this->value) / 100,
                $this->max_amount ?? $subtotal
            ),
        };
    }

    /**
     * Obtiene el nombre detallado del descuento: "Nombre (10% - Máx $100)" o "Nombre ($50.00 OFF)"
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->type->value === \App\Enums\DiscountType::Fixed->value) {
            return "{$this->name} ($" . number_format($this->value, 2) . " OFF)";
        }

        $label = "{$this->name} ({$this->value}%)";

        if ($this->max_amount > 0) {
            $label .= " [Tope: $" . number_format($this->max_amount, 2) . "]";
        }

        return $label;
    }
}
