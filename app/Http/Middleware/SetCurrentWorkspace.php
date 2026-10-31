<?php

namespace App\Http\Middleware;

use App\Enums\WorkspaceRole;
use App\Enums\WorkspaceType;
use App\Services\WorkspaceService;
use App\Support\WorkspaceContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Define o espaço ativo do usuário logado e bloqueia alterações para
 * membros com papel "somente leitura".
 */
class SetCurrentWorkspace
{
    public function __construct(
        private readonly WorkspaceContext $context,
        private readonly WorkspaceService $workspaces,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $workspace = $user->current_workspace_id
            ? $user->workspaces()->whereKey($user->current_workspace_id)->first()
            : null;

        $workspace ??= $user->workspaces()->first();

        if (! $workspace) {
            $workspace = $this->workspaces->create($user, 'Finanças pessoais', WorkspaceType::Personal);
            $workspace = $user->workspaces()->whereKey($workspace->id)->first();
        }

        if ($user->current_workspace_id !== $workspace->id) {
            $user->forceFill(['current_workspace_id' => $workspace->id])->save();
        }

        $role = WorkspaceRole::from($workspace->pivot->role);
        $this->context->set($workspace, $role);

        if (! $role->canWrite() && ! $request->isMethodSafe() && ! $request->routeIs('workspaces.switch', 'logout')) {
            abort(403, 'Seu acesso a este espaço é somente leitura.');
        }

        view()->share('currentWorkspace', $workspace);
        view()->share('currentRole', $role);

        return $next($request);
    }
}
