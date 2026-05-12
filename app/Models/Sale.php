<?php

namespace App\Models;

use App\Enums\CurrencyType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use App\Enums\SaleStatus;
use App\Enums\SaleType;
use App\Models\Concerns\HasCurrency;
use App\Services\CurrencyExchangeService;

class Sale extends Model
{
    use SoftDeletes, HasCurrency;

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
        'discount_id',
        'discount_amount',
        'totals',
        'customer_id',
        'requires_invoice',
        'customer_type',
        'notes',
        'sale_date',
        'exchange_rate',
    ];

    /**
     * Los atributos que deben ser casteados.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status'    => SaleStatus::class,
        'sale_type' => SaleType::class,
        'totals'    => 'array',
        'requires_invoice' => 'boolean',
    ];

    // ===== RELACIONES =====

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

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

    public function getFormattedTotalsAttribute(): array
    {
        return collect($this->totals ?? [])->map(function ($amount, $currency) {
            return sprintf(
                '%s %s',
                CurrencyType::from($currency)->symbol(),
                number_format($amount, 2, ',', '.')
            );
        })->toArray();
    }

    /**
     * Accessor para obtener el total consolidado en pesos automáticamente
     */
    public function getTotalGeneralArsAttribute(): float
    {
        return $this->getTotalInCurrency(\App\Enums\CurrencyType::ARS);
    }

    /**
     * Calcula el total consolidado en una moneda específica
     */
    public function getTotalInCurrency(CurrencyType $target = CurrencyType::ARS, ?float $rate = null): float
    {
        $totals = $this->totals ?? [];
        $sum = 0;

        // Si no pasan un rate, lo obtenemos del servicio
        if ($rate === null) {
            $exchangeService = app(CurrencyExchangeService::class);
            $rate = $exchangeService->getCurrentDollarRate();
        }

        foreach ($totals as $currencyId => $amount) {
            $currentCurrency = CurrencyType::tryFrom((int)$currencyId);
            if (!$currentCurrency) continue;

            if ($currentCurrency === $target) {
                $sum += $amount;
            } elseif ($target === CurrencyType::ARS && $currentCurrency === CurrencyType::USD) {
                $sum += $amount * $rate;
            } elseif ($target === CurrencyType::USD && $currentCurrency === CurrencyType::ARS) {
                $sum += ($rate > 0) ? ($amount / $rate) : 0;
            }
        }

        return $sum;
    }

    public function getNetTotalAttribute()
    {
        return $this->items()->sum('subtotal') - $this->discount_amount;
    }

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

    public function hasDiscount(): bool
    {
        return $this->discount_id !== null && $this->discount_amount > 0;
    }

    public function generateWhatsAppMessage(): string
    {
        $customerName = $this->customer_name;

        $itemsDetail = $this->items->take(5)->map(function ($item) {
            return "• {$item->quantity}x " . ($item->product->name ?? 'Producto');
        })->implode("\n");

        if ($this->items->count() > 5) {
            $itemsDetail .= "\n... y otros productos.";
        }

        $totalFormatted = $this->formatted_total;

        return "Hola *{$customerName}*, te contacto desde la sucursal por tu pedido *#{$this->id}*:\n\n"
            . "Detalle:\n{$itemsDetail}\n\n"
            . "*Total: {$totalFormatted}*";
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }
}
