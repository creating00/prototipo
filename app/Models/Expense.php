<?php

namespace App\Models;

use App\Enums\CurrencyType;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'branch_id',
        'expense_type_id',
        'amount',
        'currency',
        'payment_type',
        'reference',
    ];

    /** 
     *  Casts: convierte currency y payment_type en enums. 
     */
    protected $casts = [
        'currency' => CurrencyType::class,
        'payment_type' => PaymentType::class,
    ];

    protected $appends = ['currency_code', 'currency_symbol'];

    public function getCurrencyCodeAttribute(): ?string
    {
        return $this->currency?->code();
    }
    public function getCurrencySymbolAttribute(): ?string
    {
        return $this->currency?->symbol();
    }

    /**
     * Relación: el gasto pertenece a un usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación: el gasto pertenece a una sucursal.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relación: el gasto pertenece a un tipo de gasto.
     */
    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
