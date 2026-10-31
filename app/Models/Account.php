<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use BelongsToWorkspace;

    protected $fillable = [
        'workspace_id', 'name', 'type', 'institution', 'initial_balance', 'credit_limit',
        'color', 'include_in_total', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'initial_balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'include_in_total' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(Transaction::class, 'destination_account_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /**
     * Adiciona a coluna "balance" (saldo atual) calculada pelo banco:
     * saldo inicial + receitas − despesas − transferências enviadas + transferências recebidas,
     * considerando apenas lançamentos efetivados (status = paid).
     */
    public function scopeWithBalance(Builder $query, ?string $until = null): void
    {
        $dateFilter = $until ? ' and t.date <= ?' : '';
        $bindings = $until ? [$until, $until] : [];

        $query->select('accounts.*')->selectRaw(
            'accounts.initial_balance'
            ." + coalesce((select sum(case when t.type = 'income' then t.amount else -t.amount end) from transactions t where t.account_id = accounts.id and t.status = 'paid'{$dateFilter}), 0)"
            ." + coalesce((select sum(t.amount) from transactions t where t.destination_account_id = accounts.id and t.type = 'transfer' and t.status = 'paid'{$dateFilter}), 0)"
            .' as balance',
            $bindings
        );
    }

    public function currentBalance(): float
    {
        return (float) (static::query()->withBalance()->find($this->id)?->balance ?? 0);
    }
}
