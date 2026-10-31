<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Paid = 'paid';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Efetivada',
            self::Pending => 'Pendente',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
