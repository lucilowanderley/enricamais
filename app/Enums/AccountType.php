<?php

namespace App\Enums;

enum AccountType: string
{
    case Checking = 'checking';
    case Savings = 'savings';
    case Cash = 'cash';
    case CreditCard = 'credit_card';
    case Investment = 'investment';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Checking => 'Conta corrente',
            self::Savings => 'Poupança',
            self::Cash => 'Dinheiro / Carteira',
            self::CreditCard => 'Cartão de crédito',
            self::Investment => 'Conta investimento',
            self::Other => 'Outra',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
