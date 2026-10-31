<x-layouts.app title="Lançamentos">
    <x-page-header title="Lançamentos" subtitle="Receitas, despesas e transferências">
        <x-slot:actions>
            <x-month-nav :month="$month" route="transactions.index" :params="$filters" />
            <a href="{{ route('transactions.export', array_merge($filters, ['month' => $month->format('Y-m')])) }}" class="btn-secondary">Exportar CSV</a>
            @can('write')<a href="{{ route('transactions.create') }}" class="btn-primary">+ Novo</a>@endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid gap-4 sm:grid-cols-3">
        <x-stat label="Receitas" :value="money($income)" tone="green" />
        <x-stat label="Despesas" :value="money($expense)" tone="red" />
        <x-stat label="Resultado" :value="money($income - $expense)" :tone="$income - $expense < 0 ? 'red' : 'green'" />
    </div>

    <form method="GET" class="card mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
        <input class="input lg:col-span-2" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Buscar descrição, favorecido...">
        <select class="input" name="type">
            <option value="">Todos os tipos</option>
            @foreach (\App\Enums\TransactionType::options() as $v => $l)<option value="{{ $v }}" @selected(($filters['type'] ?? '') === $v)>{{ $l }}</option>@endforeach
        </select>
        <select class="input" name="account">
            <option value="">Todas as contas</option>
            @foreach ($accounts as $a)<option value="{{ $a->id }}" @selected((string) ($filters['account'] ?? '') === (string) $a->id)>{{ $a->name }}</option>@endforeach
        </select>
        <select class="input" name="category">
            <option value="">Todas as categorias</option>
            @foreach ($categories as $c)<option value="{{ $c->id }}" @selected((string) ($filters['category'] ?? '') === (string) $c->id)>{{ $c->full_name }}</option>@endforeach
        </select>
        <div class="flex gap-2">
            <select class="input" name="status">
                <option value="">Situação</option>
                @foreach (\App\Enums\TransactionStatus::options() as $v => $l)<option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>@endforeach
            </select>
            <button class="btn-secondary">Filtrar</button>
        </div>
    </form>

    <div class="card overflow-x-auto p-0">
        <table class="table">
            <thead><tr><th>Data</th><th>Descrição</th><th>Categoria</th><th>Conta</th><th>Situação</th><th class="text-right">Valor</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($transactions as $t)
                <tr class="hover:bg-slate-50">
                    <td class="text-slate-500">{{ $t->date->format('d/m/Y') }}</td>
                    <td>
                        <span class="font-medium text-slate-800">{{ $t->description }}</span>
                        @if ($t->counterparty)<span class="block text-xs text-slate-400">{{ $t->counterparty }}</span>@endif
                    </td>
                    <td>
                        @if ($t->type->value === 'transfer')
                            <span class="badge bg-slate-100 text-slate-600">Transferência</span>
                        @elseif ($t->category)
                            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full" style="background: {{ $t->category->color }}"></span>{{ $t->category->full_name }}</span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="text-slate-500">{{ $t->account->name }}{{ $t->destinationAccount ? ' → '.$t->destinationAccount->name : '' }}</td>
                    <td>
                        @can('write')
                            <form method="POST" action="{{ route('transactions.toggle', $t) }}">@csrf @method('PATCH')
                                <button class="badge {{ $t->status->value === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}" title="Clique para alternar">{{ $t->status->label() }}</button>
                            </form>
                        @else
                            <span class="badge {{ $t->status->value === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $t->status->label() }}</span>
                        @endcan
                    </td>
                    <td class="text-right font-medium"><x-amount :value="$t->amount" :type="$t->type->value" /></td>
                    <td class="text-right">@can('write')<a href="{{ route('transactions.edit', $t) }}" class="text-xs text-brand-700 hover:underline">Editar</a>@endcan</td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-10 text-center text-slate-500">Nenhum lançamento encontrado.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $transactions->links() }}</div>
</x-layouts.app>
