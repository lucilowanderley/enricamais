<x-layouts.app title="Espaços">
    <x-page-header title="Meus espaços" subtitle="Separe finanças pessoais, da família e de cada empresa">
        <x-slot:actions><a href="{{ route('workspaces.create') }}" class="btn-primary">+ Novo espaço</a></x-slot:actions>
    </x-page-header>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($workspaces as $ws)
            <div class="card {{ $ws->id === $currentWorkspace->id ? 'ring-2 ring-brand-500' : '' }}">
                <span class="badge {{ ['personal' => 'bg-sky-50 text-sky-700', 'family' => 'bg-violet-50 text-violet-700', 'business' => 'bg-amber-50 text-amber-700'][$ws->type->value] }}">{{ $ws->type->label() }}</span>
                <p class="mt-2 text-lg font-semibold">{{ $ws->name }}</p>
                <p class="text-sm text-slate-500">{{ $ws->members_count }} membro(s) · seu papel: {{ \App\Enums\WorkspaceRole::from($ws->pivot->role)->label() }}</p>
                @if ($ws->id !== $currentWorkspace->id)
                    <form method="POST" action="{{ route('workspaces.switch', $ws->id) }}" class="mt-4">@csrf<button class="btn-secondary btn-sm">Acessar</button></form>
                @else
                    <p class="mt-4 text-sm font-medium text-brand-700">Espaço ativo</p>
                @endif
            </div>
        @endforeach
    </div>
</x-layouts.app>
