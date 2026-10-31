<?php

namespace Tests;

use App\Enums\WorkspaceRole;
use App\Enums\WorkspaceType;
use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceService;
use App\Support\WorkspaceContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** Cria usuário + espaço e ativa o espaço no contexto (para criar dados direto nos testes). */
    protected function userWithWorkspace(WorkspaceType $type = WorkspaceType::Personal): array
    {
        $user = User::factory()->create();
        $workspace = app(WorkspaceService::class)->create($user, 'Espaço de '.$user->name, $type);
        $this->useWorkspace($workspace);

        return [$user->fresh(), $workspace];
    }

    protected function useWorkspace(Workspace $workspace, WorkspaceRole $role = WorkspaceRole::Owner): void
    {
        app(WorkspaceContext::class)->set($workspace, $role);
    }
}
