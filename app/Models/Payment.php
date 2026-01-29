<?php

namespace App\Models;

use App\Enums\CurrencyType;
use App\Enums\PaymentType;
use App\Models\Concerns\HasCurrency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes, HasCurrency;

    protected $fillable = [
        'branch_id',
        'payment_type',
        'currency',
        'amount',
        'user_id',
        'paymentable_id',
        'paymentable_type',
        'payment_method_id',
        'payment_method_type',
    ];

    protected $casts = [
        'payment_type' => PaymentType::class,
        'currency' => CurrencyType::class,
    ];

    protected static function booted()
    {
        static::creating(function ($payment) {
            if (!$payment->currency) {
                $payment->currency = \App\Enums\CurrencyType::ARS;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentable()
    {
        return $this->morphTo();
    }

    public function paymentMethod()
    {
        return $this->morphTo();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function getFormattedAmountAttribute(): string
    {
        return sprintf(
            '%s %s',
            $this->currency->symbol(),
            number_format($this->amount, 2, ',', '.')
        );
    }
}
