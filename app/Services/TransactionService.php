<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionService
{
    /**
     * Cria um lançamento. Com "installments" > 1 divide o valor em parcelas mensais
     * (a primeira na data informada, as demais como pendentes).
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, Transaction>
     */
    public function create(array $data, int $installments = 1): Collection
    {
        $type = TransactionType::from($data['type']);
        if ($type === TransactionType::Transfer) {
            $data['category_id'] = null;
        } else {
            $data['destination_account_id'] = null;
        }

        if ($installments <= 1) {
            return collect([Transaction::create($data)]);
        }

        return DB::transaction(function () use ($data, $installments) {
            $total = round((float) $data['amount'], 2);
            $base = floor($total / $installments * 100) / 100;
            $remainder = round($total - $base * $installments, 2);
            $group = (string) Str::uuid();
            $date = Carbon::parse($data['date']);

            $created = collect();
            for ($i = 1; $i <= $installments; $i++) {
                $created->push(Transaction::create(array_merge($data, [
                    'amount' => $i === 1 ? $base + $remainder : $base,
                    'date' => $date->copy()->addMonthsNoOverflow($i - 1)->toDateString(),
                    'description' => $data['description']." ({$i}/{$installments})",
                    'status' => $i === 1 ? $data['status'] : TransactionStatus::Pending->value,
                    'installment_group' => $group,
                    'installment_number' => $i,
                    'installment_total' => $installments,
                ])));
            }

            return $created;
        });
    }
}
