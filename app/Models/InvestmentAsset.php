<?php

namespace App\Models;

use App\Enums\AssetClass;
use App\Models\Concerns\BelongsToWorkspace;
use App\Models\Concerns\StoresDateOnly;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestmentAsset extends Model
{
    use BelongsToWorkspace, StoresDateOnly;

    protected $fillable = [
        'workspace_id', 'name', 'ticker', 'asset_class', 'currency', 'institution',
        'rate_description', 'maturity_date', 'current_price', 'price_updated_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'asset_class' => AssetClass::class,
            'maturity_date' => 'date',
            'current_price' => 'decimal:8',
            'price_updated_at' => 'datetime',
        ];
    }

    protected function maturityDate(): Attribute
    {
        return static::dateOnlyAttribute();
    }

    public function operations(): HasMany
    {
        return $this->hasMany(InvestmentTransaction::class)->orderBy('date')->orderBy('id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->ticker ? "{$this->ticker} — {$this->name}" : $this->name;
    }
}
