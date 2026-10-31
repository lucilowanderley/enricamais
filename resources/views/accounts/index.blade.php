<x-layouts.app title="Contas">
    <x-page-header title="Contas" subtitle="Contas bancárias, carteiras, cartões e contas de investimento">
        <x-slot:actions>
            @can('write')<a href="{{ route('accounts.create') }}" class="btn-primary">+ Nova conta</a>@endcan
        </x-slot:actions>
    </x-page-header>

    <x-stat class="mb-6" label="Saldo consolidado" :value="money($total)" :tone="$total < 0 ? 'red' : 'slate'" hint="Soma das contas ativas marcadas para compor o total" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($accounts as $account)
            <div class="card relative overflow-hidden {{ $account->archived_at ? 'opacity-60' : '' }}">
                <span class="absolute inset-y-0 left-0 w-1.5" style="background: {{ $account->color }}"></span>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-semibold">{{ $account->name }}</p>
                        <p class="text-xs text-slate-500">{{ $account->type->label() }}{{ $account->institution ? ' · '.$account->institution : '' }}</p>
                    </div>
                    @if ($account->archived_at)<span class="badge bg-slate-100 text-slate-600">Arquivada</span>@endif
                </div>
                <p class="mt-4 text-2xl font-semibold"><x-amount :value="$account->balance" /></p>
                @if ($account->credit_limit)
                    <p class="text-xs text-slate-500">Limite: {{ money($account->credit_limit) }} · Disponível: {{ money($account->credit_limit + $account->balance) }}</p>
                @endif
                <div class="mt-4 flex gap-2">
                    <a href="{{ route('accounts.show', $account) }}" class="btn-secondary btn-sm">Extrato</a>
                    @can('write')<a href="{{ route('accounts.edit', $account) }}" class="btn-secondary btn-sm">Editar</a>@endcan
                </div>
            </div>
        @empty
            <x-empty message="Nenhuma conta cadastrada." />
        @endforelse
    </div>
</x-layouts.app>
