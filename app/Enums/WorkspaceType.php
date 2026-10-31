<?php

namespace App\Enums;

enum WorkspaceType: string
{
    case Personal = 'personal';
    case Family = 'family';
    case Business = 'business';

    public function label(): string
    {
        return match ($this) {
            self::Personal => 'Pessoal',
            self::Family => 'Família',
            self::Business => 'Empresarial',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
