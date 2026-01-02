<?php

namespace App\Models;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'user_id',
        'status',
        'source',
        'sale_id',
        'total_amount',
        'notes',
        'customer_id',
        'customer_type',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'source' => OrderSource::class,
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

    public function reception():HasOne
    {
        return $this->hasOne(OrderReception::class);
    }

    public function customer(): MorphTo
    {
        return $this->morphTo();
    }

    public function getAmountToChargeAttribute(): float
    {
        return $this->total_amount;
    }

    public function isInterBranch(): bool
    {
        return $this->customer_type === Branch::class;
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
        $customerName = $this->customer_name; // Usando el accesor que ya tienes

        $itemsDetail = $this->items->take(5)->map(function ($item) {
            return "• {$item->quantity}x " . ($item->product->name ?? 'Producto');
        })->implode("\n");

        if ($this->items->count() > 5) {
            $itemsDetail .= "\n... y otros productos.";
        }

        $totalFormatted = "ARS " . number_format($this->total_amount, 2, ',', '.');

        return "Hola *{$customerName}*, te contacto desde la sucursal por tu pedido *#{$this->id}*:\n\n"
            . "Detalle:\n{$itemsDetail}\n\n"
            . "*Total: {$totalFormatted}*";
    }
}
