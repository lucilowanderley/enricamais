<?php

namespace App\Support;

use App\Enums\WorkspaceRole;
use App\Models\Workspace;

/**
 * Guarda o espaço (pessoal, família ou empresa) ativo na requisição atual.
 * Todos os modelos financeiros são filtrados automaticamente por ele.
 */
class WorkspaceContext
{
    private ?Workspace $workspace = null;

    private ?WorkspaceRole $role = null;

    public function set(?Workspace $workspace, ?WorkspaceRole $role = null): void
    {
        $this->workspace = $workspace;
        $this->role = $role;
    }

    public function get(): ?Workspace
    {
        return $this->workspace;
    }

    public function id(): ?int
    {
        return $this->workspace?->id;
    }

    public function role(): ?WorkspaceRole
    {
        return $this->role;
    }

    public function canWrite(): bool
    {
        return (bool) $this->role?->canWrite();
    }

    public function canManage(): bool
    {
        return (bool) $this->role?->canManage();
    }
}
