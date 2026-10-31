<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use App\Models\Concerns\StoresDateOnly;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    use BelongsToWorkspace, StoresDateOnly;

    protected $fillable = ['workspace_id', 'name', 'target_amount', 'current_amount', 'target_date', 'color', 'notes'];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'current_amount' => 'decimal:2',
            'target_date' => 'date',
        ];
    }

    protected function targetDate(): Attribute
    {
        return static::dateOnlyAttribute();
    }

    public function progress(): float
    {
        $target = (float) $this->target_amount;

        return $target > 0 ? min(100, round((float) $this->current_amount / $target * 100, 1)) : 0;
    }

    /** Quanto guardar por mês para atingir a meta no prazo. */
    public function monthlyNeeded(): ?float
    {
        if (! $this->target_date) {
            return null;
        }
        $remaining = max(0, (float) $this->target_amount - (float) $this->current_amount);
        $months = max(1, (int) ceil(now()->startOfMonth()->diffInMonths($this->target_date->copy()->startOfMonth())));

        return round($remaining / $months, 2);
    }
}
