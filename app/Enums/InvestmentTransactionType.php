<?php

namespace App\Enums;

enum InvestmentTransactionType: string
{
    case Buy = 'buy';
    case Sell = 'sell';
    case Income = 'income';

    public function label(): string
    {
        return match ($this) {
            self::Buy => 'Compra / Aporte',
            self::Sell => 'Venda / Resgate',
            self::Income => 'Provento / Rendimento',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
