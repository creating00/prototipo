<?php

namespace App\Models;

use App\Enums\CurrencyType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'user_id',
        'status',
        'source',
        'sale_id',
        'totals',
        'notes',
        'customer_id',
        'customer_type',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'source' => OrderSource::class,
        'totals' => 'array',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items')
            ->withPivot('quantity', 'unit_price', 'subtotal')
            ->withTimestamps();
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function reception(): HasOne
    {
        return $this->hasOne(OrderReception::class);
    }

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

    public function customer(): MorphTo
    {
        return $this->morphTo();
    }

    public function isInterBranch(): bool
    {
        return $this->customer_type === Branch::class;
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    public function scopePending($query)
    {
        return $query->where('status', OrderStatus::Pending);
    }

    public function getCustomerNameAttribute(): string
    {
        if ($this->customer) {
            return $this->isInterBranch()
                ? $this->customer->name ?? "Sucursal {$this->customer->id}"
                : $this->customer->full_name ?? '';
        }
        return '';
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

        $totals = collect($this->formatted_totals)
            ->map(fn($value) => "• {$value}")
            ->implode("\n");

        return "Hola *{$customerName}*, te contacto desde la sucursal por tu pedido *#{$this->id}*:\n\n"
            . "Detalle:\n{$itemsDetail}\n\n"
            . "*Total: {$totals}*";
    }
}
