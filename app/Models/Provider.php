<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_name',
        'tax_id',
        'short_name',
        'contact_name',
        'email',
        'phone',
        'address',
    ];

    protected $appends = ['display_name'];

    public function getDisplayNameAttribute(): string
    {
        return "[{$this->tax_id}] {$this->business_name} - {$this->short_name}";
    }

    public function getDisplayNameHtmlAttribute(): string
    {
        // Solo ponemos el documento en negritas
        return "<strong>[{$this->tax_id}]</strong> {$this->business_name}";
    }

    public function displayName(bool $html = false): string
    {
        return $html ? $this->display_name_html : $this->display_name;
    }

    public function providerProducts(): HasMany
    {
        return $this->hasMany(ProviderProduct::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'provider_products')
            ->withPivot([
                'id',
                'provider_code',
                'lead_time_days',
                'status',
                'deleted_at',
            ])
            ->withTimestamps();
    }
}
