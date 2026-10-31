<x-layouts.app title="Configurações do espaço">
    <x-page-header title="Configurações do espaço" :subtitle="$workspace->name" />

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('workspaces.update') }}" class="card space-y-4">
            @csrf @method('PUT')
            <h2 class="font-semibold">Dados do espaço</h2>
            @php $disabled = ! $currentRole->canManage(); @endphp
            <div>
                <label class="label">Nome</label>
                <input class="input" name="name" value="{{ old('name', $workspace->name) }}" @disabled($disabled) required>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="label">Tipo</label>
                    <select class="input" name="type" @disabled($disabled)>
                        @foreach (\App\Enums\WorkspaceType::options() as $v => $l)<option value="{{ $v }}" @selected($workspace->type->value === $v)>{{ $l }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Moeda</label>
                    <select class="input" name="currency" @disabled($disabled)>
                        @foreach (['BRL' => 'Real', 'USD' => 'Dólar', 'EUR' => 'Euro'] as $v => $l)<option value="{{ $v }}" @selected($workspace->currency === $v)>{{ $l }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="label">CPF/CNPJ</label>
                    <input class="input" name="document" value="{{ old('document', $workspace->document) }}" @disabled($disabled)>
                </div>
            </div>
            @can('manage-workspace')<div class="flex justify-end"><button class="btn-primary">Salvar</button></div>@endcan
        </form>

        <div class="card">
            <h2 class="mb-4 font-semibold">Membros</h2>
            <ul class="divide-y divide-slate-100 text-sm">
                @foreach ($workspace->members as $member)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                        <div>
                            <p class="font-medium">{{ $member->name }}</p>
                            <p class="text-xs text-slate-500">{{ $member->email }}</p>
                        </div>
                        @if ($member->id === $workspace->owner_id || ! $currentRole->canManage())
                            <span class="badge bg-slate-100 text-slate-600">{{ \App\Enums\WorkspaceRole::from($member->pivot->role)->label() }}</span>
                        @else
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('workspaces.members.update', $member) }}">@csrf @method('PUT')
                                    <select name="role" class="input py-1 text-xs" onchange="this.form.submit()">
                                        @foreach (['admin', 'member', 'viewer'] as $r)<option value="{{ $r }}" @selected($member->pivot->role === $r)>{{ \App\Enums\WorkspaceRole::from($r)->label() }}</option>@endforeach
                                    </select>
                                </form>
                                <form method="POST" action="{{ route('workspaces.members.destroy', $member) }}" onsubmit="return confirm('Remover {{ $member->name }} do espaço?')">@csrf @method('DELETE')
                                    <button class="text-xs text-rose-600 hover:underline">Remover</button>
                                </form>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>

            @can('manage-workspace')
                <form method="POST" action="{{ route('workspaces.members.store') }}" class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                    @csrf
                    <h3 class="text-sm font-semibold">Adicionar membro</h3>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input class="input" type="email" name="email" placeholder="E-mail de quem já tem conta" required>
                        <select class="input sm:w-44" name="role">
                            <option value="member">Membro</option><option value="admin">Administrador</option><option value="viewer">Somente leitura</option>
                        </select>
                        <button class="btn-primary">Adicionar</button>
                    </div>
                    <p class="text-xs text-slate-500">Membros lançam e editam dados; administradores também gerenciam membros; "somente leitura" apenas consulta — ideal para contador ou consultor.</p>
                </form>
            @endcan
        </div>
    </div>

    @if ($workspace->owner_id === auth()->id())
        <form method="POST" action="{{ route('workspaces.destroy') }}" class="card mt-6 border-rose-200" onsubmit="return confirm('Esta ação apaga todos os dados do espaço. Continuar?')">
            @csrf @method('DELETE')
            <h2 class="font-semibold text-rose-700">Excluir espaço</h2>
            <p class="mt-1 text-sm text-slate-600">Todos os dados (contas, lançamentos, investimentos) serão apagados definitivamente. Digite <strong>{{ $workspace->name }}</strong> para confirmar.</p>
            <div class="mt-3 flex gap-2"><input class="input max-w-xs" name="confirm_name"><button class="btn-danger">Excluir</button></div>
        </form>
    @endif
</x-layouts.app>
