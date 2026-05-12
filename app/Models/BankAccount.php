<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class BankAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'bank_id',
        'alias',
        'account_number',
        'cbu',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    protected function fullDescription(): Attribute
    {
        return Attribute::make(
            get: function () {
                $bankName = $this->bank?->name ?? 'S/B';
                $userName = $this->user?->name ?? 'Sin titular';
                $identifier = $this->alias ?? ($this->account_number ?? $this->cbu);

                return "{$bankName} - {$userName} ({$identifier})";
            }
        );
    }
}
