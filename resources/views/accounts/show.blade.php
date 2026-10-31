<x-layouts.app :title="'Extrato · '.$account->name">
    <x-page-header :title="$account->name" :subtitle="$account->type->label().($account->institution ? ' · '.$account->institution : '')">
        <x-slot:actions>
            <x-month-nav :month="$month" route="accounts.show" :params="['account' => $account->id]" />
            @can('write')<a href="{{ route('transactions.create', ['account' => $account->id]) }}" class="btn-primary">+ Lançamento</a>@endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat label="Saldo no início do mês" :value="money($opening)" />
        <x-stat label="Saldo atual" :value="money($balance)" :tone="$balance < 0 ? 'red' : 'green'" />
        <x-stat label="Lançamentos no mês" :value="$transactions->count()" />
    </div>

    <div class="card overflow-x-auto p-0">
        <table class="table">
            <thead><tr><th>Data</th><th>Descrição</th><th>Categoria</th><th>Situação</th><th class="text-right">Valor</th><th class="text-right">Saldo</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @php $running = $opening; @endphp
            @forelse ($transactions as $t)
                @php
                    $incoming = $t->type->value === 'transfer' && $t->destination_account_id === $account->id;
                    $signed = $t->type->value === 'income' || $incoming ? (float) $t->amount : -(float) $t->amount;
                    if ($t->status->value === 'paid') { $running += $signed; }
                @endphp
                <tr>
                    <td>{{ $t->date->format('d/m/Y') }}</td>
                    <td>{{ $t->description }}</td>
                    <td class="text-slate-500">{{ $t->type->value === 'transfer' ? ($incoming ? 'Transferência de '.$t->account->name : 'Transferência para '.$t->destinationAccount?->name) : ($t->category?->full_name ?? '—') }}</td>
                    <td><span class="badge {{ $t->status->value === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $t->status->label() }}</span></td>
                    <td class="text-right"><x-amount :value="$signed" /></td>
                    <td class="text-right tabular-nums text-slate-600">{{ money($running) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-8 text-center text-slate-500">Sem lançamentos neste mês.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
