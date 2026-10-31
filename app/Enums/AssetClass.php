<?php

namespace App\Enums;

enum AssetClass: string
{
    case FixedIncome = 'fixed_income';
    case Treasury = 'treasury';
    case Stocks = 'stocks';
    case RealEstateFunds = 'reits';
    case Etfs = 'etfs';
    case Funds = 'funds';
    case International = 'international';
    case Crypto = 'crypto';
    case Pension = 'pension';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FixedIncome => 'Renda fixa',
            self::Treasury => 'Tesouro Direto',
            self::Stocks => 'Ações',
            self::RealEstateFunds => 'Fundos imobiliários (FIIs)',
            self::Etfs => 'ETFs',
            self::Funds => 'Fundos de investimento',
            self::International => 'Internacional',
            self::Crypto => 'Criptoativos',
            self::Pension => 'Previdência',
            self::Other => 'Outros',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }

    public function color(): string
    {
        return match ($this) {
            self::FixedIncome => '#0ea5e9',
            self::Treasury => '#6366f1',
            self::Stocks => '#16a34a',
            self::RealEstateFunds => '#f59e0b',
            self::Etfs => '#14b8a6',
            self::Funds => '#8b5cf6',
            self::International => '#ec4899',
            self::Crypto => '#f97316',
            self::Pension => '#64748b',
            self::Other => '#94a3b8',
        };
    }

    /**
     * Alocação-alvo sugerida (em %) por perfil de risco. Ponto de partida editável pelo usuário.
     *
     * @return array<string, int>
     */
    public static function suggestedAllocation(RiskProfile $profile): array
    {
        return match ($profile) {
            RiskProfile::Conservative => ['fixed_income' => 50, 'treasury' => 30, 'funds' => 5, 'reits' => 5, 'stocks' => 5, 'international' => 5],
            RiskProfile::Moderate => ['fixed_income' => 30, 'treasury' => 15, 'stocks' => 20, 'reits' => 10, 'etfs' => 10, 'international' => 10, 'crypto' => 0, 'funds' => 5],
            RiskProfile::Aggressive => ['fixed_income' => 15, 'treasury' => 5, 'stocks' => 30, 'reits' => 10, 'etfs' => 10, 'international' => 20, 'crypto' => 5, 'funds' => 5],
        };
    }
}
