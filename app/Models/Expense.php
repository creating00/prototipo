<?php

namespace App\Models;

use App\Enums\CurrencyType;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'date',
        'observation',
    ];

    protected $casts = [
        'currency' => CurrencyType::class,
        'payment_type' => PaymentType::class,
        'date' => 'date',
        'amount' => 'decimal:2',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class);
    }

    // Scopes de filtrado
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeToday($query)
    {
        // Se usa el campo 'date' para reflejar el día del gasto real
        return $query->whereDate('date', Carbon::today());
    }

    public function scopeThisMonth($query)
    {
        return $query
            ->whereYear('date', Carbon::now()->year)
            ->whereMonth('date', Carbon::now()->month);
    }
}
