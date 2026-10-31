<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Concerns\BelongsToWorkspace;
use App\Models\Concerns\StoresDateOnly;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use BelongsToWorkspace, StoresDateOnly;

    protected $fillable = [
        'workspace_id', 'account_id', 'destination_account_id', 'category_id', 'user_id',
        'type', 'status', 'amount', 'date', 'description', 'counterparty', 'notes',
        'installment_group', 'installment_number', 'installment_total',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'amount' => 'decimal:2',
            'date' => 'date',
        ];
    }

    protected function date(): Attribute
    {
        return static::dateOnlyAttribute();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'destination_account_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePaid(Builder $query): void
    {
        $query->where('status', TransactionStatus::Paid->value);
    }

    public function scopeBetween(Builder $query, string $from, string $to): void
    {
        $query->whereBetween('date', [$from, $to]);
    }

    public function signedAmount(): float
    {
        return match ($this->type) {
            TransactionType::Income => (float) $this->amount,
            TransactionType::Expense => -(float) $this->amount,
            TransactionType::Transfer => 0.0,
        };
    }
}
