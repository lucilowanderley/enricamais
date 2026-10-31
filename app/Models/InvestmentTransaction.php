<?php

namespace App\Models;

use App\Enums\InvestmentTransactionType;
use App\Models\Concerns\BelongsToWorkspace;
use App\Models\Concerns\StoresDateOnly;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentTransaction extends Model
{
    use BelongsToWorkspace, StoresDateOnly;

    protected $fillable = [
        'workspace_id', 'investment_asset_id', 'type', 'date', 'quantity', 'unit_price', 'fees', 'amount', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => InvestmentTransactionType::class,
            'date' => 'date',
            'quantity' => 'decimal:8',
            'unit_price' => 'decimal:8',
            'fees' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    protected function date(): Attribute
    {
        return static::dateOnlyAttribute();
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(InvestmentAsset::class, 'investment_asset_id');
    }
}
