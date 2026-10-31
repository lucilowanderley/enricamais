<?php

namespace App\Enums;

enum RiskProfile: string
{
    case Conservative = 'conservative';
    case Moderate = 'moderate';
    case Aggressive = 'aggressive';

    public function label(): string
    {
        return match ($this) {
            self::Conservative => 'Conservador',
            self::Moderate => 'Moderado',
            self::Aggressive => 'Arrojado',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
