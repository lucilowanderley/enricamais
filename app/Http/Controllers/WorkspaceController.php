<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceRole;
use App\Enums\WorkspaceType;
use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceService;
use App\Support\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function index(Request $request): View
    {
        return view('workspaces.index', [
            'workspaces' => $request->user()->workspaces()->withCount('members')->get(),
        ]);
    }

    public function create(): View
    {
        return view('workspaces.create');
    }

    public function store(Request $request, WorkspaceService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(WorkspaceType::class)],
            'currency' => ['required', Rule::in(['BRL', 'USD', 'EUR'])],
            'document' => ['nullable', 'string', 'max:30'],
        ]);

        $workspace = $service->create($request->user(), $data['name'], WorkspaceType::from($data['type']), $data['currency'], $data['document'] ?? null);
        $request->user()->forceFill(['current_workspace_id' => $workspace->id])->save();

        return redirect()->route('dashboard')->with('success', "Espaço \"{$workspace->name}\" criado.");
    }

    public function switch(Request $request, int $workspace): RedirectResponse
    {
        $target = $request->user()->workspaces()->whereKey($workspace)->firstOrFail();
        $request->user()->forceFill(['current_workspace_id' => $target->id])->save();

        return redirect()->route('dashboard')->with('success', "Você está em \"{$target->name}\".");
    }

    public function settings(WorkspaceContext $context): View
    {
        $workspace = $context->get()->load('members');

        return view('workspaces.settings', compact('workspace'));
    }

    public function update(Request $request, WorkspaceContext $context): RedirectResponse
    {
        Gate::authorize('manage-workspace');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(WorkspaceType::class)],
            'currency' => ['required', Rule::in(['BRL', 'USD', 'EUR'])],
            'document' => ['nullable', 'string', 'max:30'],
        ]);

        $context->get()->update($data);

        return back()->with('success', 'Espaço atualizado.');
    }

    public function addMember(Request $request, WorkspaceContext $context): RedirectResponse
    {
        Gate::authorize('manage-workspace');

        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', Rule::in([WorkspaceRole::Admin->value, WorkspaceRole::Member->value, WorkspaceRole::Viewer->value])],
        ], ['email.exists' => 'Não há usuário cadastrado com este e-mail. Peça para a pessoa criar uma conta primeiro.']);

        $workspace = $context->get();
        $user = User::where('email', $data['email'])->firstOrFail();

        if ($workspace->members()->whereKey($user->id)->exists()) {
            return back()->withErrors(['email' => 'Esta pessoa já faz parte do espaço.']);
        }

        $workspace->members()->attach($user->id, ['role' => $data['role']]);

        return back()->with('success', "{$user->name} foi adicionado(a) ao espaço.");
    }

    public function updateMember(Request $request, WorkspaceContext $context, User $user): RedirectResponse
    {
        Gate::authorize('manage-workspace');
        $workspace = $context->get();
        abort_if($user->id === $workspace->owner_id, 422, 'O papel do proprietário não pode ser alterado.');

        $data = $request->validate([
            'role' => ['required', Rule::in([WorkspaceRole::Admin->value, WorkspaceRole::Member->value, WorkspaceRole::Viewer->value])],
        ]);

        $workspace->members()->updateExistingPivot($user->id, ['role' => $data['role']]);

        return back()->with('success', 'Permissão atualizada.');
    }

    public function removeMember(WorkspaceContext $context, User $user): RedirectResponse
    {
        Gate::authorize('manage-workspace');
        $workspace = $context->get();
        abort_if($user->id === $workspace->owner_id, 422, 'O proprietário não pode ser removido.');

        $workspace->members()->detach($user->id);
        if ($user->current_workspace_id === $workspace->id) {
            $user->forceFill(['current_workspace_id' => null])->save();
        }

        return back()->with('success', 'Membro removido.');
    }

    public function destroy(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $workspace = $context->get();
        abort_unless($workspace->owner_id === $request->user()->id, 403, 'Apenas o proprietário pode excluir o espaço.');

        if ($request->user()->workspaces()->count() <= 1) {
            return back()->withErrors(['workspace' => 'Você precisa manter pelo menos um espaço.']);
        }

        $request->validate(['confirm_name' => ['required', Rule::in([$workspace->name])]], [
            'confirm_name.in' => 'Digite o nome do espaço exatamente para confirmar.',
        ]);

        Workspace::whereKey($workspace->id)->delete();
        $request->user()->forceFill(['current_workspace_id' => null])->save();

        return redirect()->route('dashboard')->with('success', 'Espaço excluído.');
    }
}
