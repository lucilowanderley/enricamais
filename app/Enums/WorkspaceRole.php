<?php

namespace App\Enums;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Proprietário',
            self::Admin => 'Administrador',
            self::Member => 'Membro',
            self::Viewer => 'Somente leitura',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }

    public function canWrite(): bool
    {
        return $this !== self::Viewer;
    }

    public function canManage(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }
}
