<x-layouts.app title="Painel">
    <x-page-header title="Painel" :subtitle="$currentWorkspace->name.' · '.ucfirst(now()->translatedFormat('F \d\e Y'))" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Saldo em contas" :value="money($totalBalance)" :tone="$totalBalance < 0 ? 'red' : 'slate'" />
        <x-stat label="Receitas do mês" :value="money($monthIncome)" tone="green" />
        <x-stat label="Despesas do mês" :value="money($monthExpense)" tone="red" />
        <x-stat label="Investimentos" :value="money($portfolio['market_value'])" tone="blue"
                :hint="$portfolio['invested'] > 0 ? 'Rentabilidade: '.number_format($portfolio['unrealized_percent'], 2, ',', '.').'%' : 'Cadastre seus ativos'" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold">Fluxo de caixa — últimos 6 meses</h2>
                <a href="{{ route('reports.index') }}" class="text-sm text-brand-700 hover:underline">Relatórios</a>
            </div>
            <div class="h-72">
                <canvas data-chart="{{ json_encode([
                    'type' => 'bar',
                    'data' => [
                        'labels' => $cashflow->pluck('label'),
                        'datasets' => [
                            ['label' => 'Receitas', 'data' => $cashflow->pluck('income'), 'backgroundColor' => '#16a34a', 'borderRadius' => 4],
                            ['label' => 'Despesas', 'data' => $cashflow->pluck('expense'), 'backgroundColor' => '#e11d48', 'borderRadius' => 4],
                        ],
                    ],
                ]) }}"></canvas>
            </div>
        </div>

        <div class="card">
            <h2 class="mb-4 font-semibold">Despesas por categoria</h2>
            @if ($expensesByCategory->isEmpty())
                <x-empty message="Nenhuma despesa efetivada neste mês." />
            @else
                <div class="h-48">
                    <canvas data-chart="{{ json_encode([
                        'type' => 'doughnut',
                        'data' => [
                            'labels' => $expensesByCategory->pluck('name'),
                            'datasets' => [['data' => $expensesByCategory->pluck('total'), 'backgroundColor' => $expensesByCategory->pluck('color')]],
                        ],
                        'options' => ['plugins' => ['legend' => ['display' => false]]],
                    ]) }}"></canvas>
                </div>
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ($expensesByCategory->take(5) as $row)
                        <li class="flex items-center justify-between">
                            <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $row['color'] }}"></span>{{ $row['name'] }}</span>
                            <span class="tabular-nums text-slate-600">{{ money($row['total']) }} <span class="text-xs text-slate-400">({{ number_format($row['percent'], 1, ',', '.') }}%)</span></span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold">Contas</h2>
                <a href="{{ route('accounts.index') }}" class="text-sm text-brand-700 hover:underline">Ver todas</a>
            </div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($accounts as $account)
                    <li class="flex items-center justify-between py-2">
                        <a href="{{ route('accounts.show', $account) }}" class="flex items-center gap-2 hover:underline">
                            <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $account->color }}"></span>{{ $account->name }}
                        </a>
                        <x-amount :value="$account->balance" />
                    </li>
                @empty
                    <li class="py-2 text-slate-500">Nenhuma conta cadastrada.</li>
                @endforelse
            </ul>
        </div>

        <div class="card">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold">Orçamentos do mês</h2>
                <a href="{{ route('budgets.index') }}" class="text-sm text-brand-700 hover:underline">Gerenciar</a>
            </div>
            @forelse ($budgets as $b)
                <div class="mb-3">
                    <div class="mb-1 flex justify-between text-sm"><span>{{ $b['category']->name }}</span><span class="tabular-nums text-slate-500">{{ money($b['spent']) }} / {{ money($b['budget']) }}</span></div>
                    <x-progress :percent="$b['percent']" />
                </div>
            @empty
                <x-empty message="Defina limites de gastos por categoria.">
                    <a href="{{ route('budgets.index') }}" class="btn-secondary btn-sm">Criar orçamento</a>
                </x-empty>
            @endforelse
        </div>

        <div class="card">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold">Contas a pagar / receber</h2>
                <a href="{{ route('transactions.index', ['status' => 'pending']) }}" class="text-sm text-brand-700 hover:underline">Pendentes</a>
            </div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($upcoming as $t)
                    <li class="flex items-center justify-between py-2">
                        <div>
                            <p class="{{ $t->date->isPast() && ! $t->date->isToday() ? 'text-rose-600' : '' }}">{{ $t->description }}</p>
                            <p class="text-xs text-slate-400">{{ $t->date->format('d/m') }} · {{ $t->account->name }}</p>
                        </div>
                        <x-amount :value="$t->amount" :type="$t->type->value" />
                    </li>
                @empty
                    <li class="py-2 text-slate-500">Nada pendente para os próximos 15 dias. 🎉</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold">Últimos lançamentos</h2>
                <a href="{{ route('transactions.index') }}" class="text-sm text-brand-700 hover:underline">Ver todos</a>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <tbody class="divide-y divide-slate-100">
                    @forelse ($recent as $t)
                        <tr>
                            <td class="text-slate-500">{{ $t->date->format('d/m') }}</td>
                            <td>{{ $t->description }}</td>
                            <td class="text-slate-500">{{ $t->type->value === 'transfer' ? $t->account->name.' → '.$t->destinationAccount?->name : ($t->category?->name ?? '—') }}</td>
                            <td class="text-right"><x-amount :value="$t->amount" :type="$t->type->value" /></td>
                        </tr>
                    @empty
                        <tr><td class="text-slate-500">Nenhum lançamento ainda. Comece registrando suas receitas e despesas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold">Metas</h2>
                <a href="{{ route('goals.index') }}" class="text-sm text-brand-700 hover:underline">Ver metas</a>
            </div>
            @forelse ($goals as $goal)
                <div class="mb-3">
                    <div class="mb-1 flex justify-between text-sm"><span>{{ $goal->name }}</span><span class="text-slate-500">{{ number_format($goal->progress(), 0) }}%</span></div>
                    <x-progress :percent="$goal->progress()" :color="$goal->color" />
                </div>
            @empty
                <x-empty message="Crie metas como reserva de emergência, viagem ou aposentadoria." />
            @endforelse
            <a href="{{ route('investments.advisor') }}" class="mt-4 flex items-center gap-3 rounded-lg bg-gradient-to-r from-brand-700 to-emerald-500 p-4 text-white hover:opacity-95">
                <span class="text-2xl">✨</span>
                <span class="text-sm"><strong class="block">Assistente de investimentos</strong>Peça um diagnóstico da sua carteira com IA</span>
            </a>
        </div>
    </div>
</x-layouts.app>
