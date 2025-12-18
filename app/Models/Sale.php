<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\SaleStatus;
use App\Enums\SaleType; // Importar el nuevo Enum

class Sale extends Model
{
    use SoftDeletes;

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'branch_id',
        'user_id',
        'internal_number',
        'sale_type',
        'status',
        'amount_received',
        'change_returned',
        'remaining_balance',
        'total_amount',
        'customer_id',
        'customer_type',
    ];

    /**
     * Los atributos que deben ser casteados.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status'    => SaleStatus::class,
        'sale_type' => SaleType::class,
    ];

    // ===== RELACIONES =====

    /**
     * Obtiene la sucursal a la que pertenece la venta.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Obtiene el usuario (vendedor) asociado a la venta.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtiene los productos/servicios (items) de la venta.
     */
    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Obtiene los pagos asociados a esta venta (relación polimórfica).
     */
    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    // ===== RELACIÓN POLIMÓRFICA =====

    /**
     * Define la relación polimórfica para el cliente.
     */
    public function customer(): MorphTo
    {
        return $this->morphTo();
    }

    // ===== HELPERS & ACCESSORS =====

    /**
     * Determina si el cliente es otra sucursal.
     *
     * @return bool
     */
    public function isInterBranch(): bool
    {
        return $this->customer_type === Branch::class;
    }

    /**
     * Determina si la venta es de tipo Reparación.
     *
     * @return bool
     */
    public function isRepair(): bool
    {
        return $this->sale_type === SaleType::Repair;
    }

    /**
     * Obtiene el nombre completo del cliente.
     *
     * @return string
     */
    public function getCustomerNameAttribute(): string
    {
        if ($this->customer) {
            return $this->isInterBranch()
                ? $this->customer->name ?? "Sucursal {$this->customer->id}"
                : $this->customer->full_name ?? '';
        }
        return '';
    }
}
