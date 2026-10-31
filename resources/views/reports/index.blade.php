<x-layouts.app title="Relatórios">
    <x-page-header title="Relatórios" :subtitle="$currentWorkspace->type->value === 'business' ? 'Fluxo de caixa, DRE gerencial e evolução patrimonial' : 'Fluxo de caixa, gastos por categoria e evolução patrimonial'" />

    <div class="card mb-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold">Fluxo de caixa mensal</h2>
            <div class="inline-flex rounded-lg border border-slate-300 bg-white text-sm">
                @foreach ([6, 12, 24] as $m)
                    <a href="{{ route('reports.index', array_merge(request()->query(), ['months' => $m])) }}" class="px-3 py-1.5 {{ $months === $m ? 'bg-brand-600 text-white' : 'hover:bg-slate-50' }} {{ $loop->first ? 'rounded-l-lg' : '' }} {{ $loop->last ? 'rounded-r-lg' : '' }}">{{ $m }} meses</a>
                @endforeach
            </div>
        </div>
        <div class="h-80">
            <canvas data-chart="{{ json_encode([
                'type' => 'bar',
                'data' => [
                    'labels' => $cashflow->pluck('label'),
                    'datasets' => [
                        ['type' => 'line', 'label' => 'Resultado', 'data' => $cashflow->pluck('net'), 'borderColor' => '#0ea5e9', 'backgroundColor' => '#0ea5e9', 'tension' => 0.3],
                        ['label' => 'Receitas', 'data' => $cashflow->pluck('income'), 'backgroundColor' => '#16a34a', 'borderRadius' => 4],
                        ['label' => 'Despesas', 'data' => $cashflow->pluck('expense'), 'backgroundColor' => '#e11d48', 'borderRadius' => 4],
                    ],
                ],
            ]) }}"></canvas>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="table">
                <thead><tr><th>Mês</th><th class="text-right">Receitas</th><th class="text-right">Despesas</th><th class="text-right">Resultado</th><th class="text-right">Poupança</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @foreach ($cashflow->reverse() as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="text-right tabular-nums text-emerald-600">{{ money($row['income']) }}</td>
                        <td class="text-right tabular-nums text-rose-600">{{ money($row['expense']) }}</td>
                        <td class="text-right"><x-amount :value="$row['net']" /></td>
                        <td class="text-right text-slate-500">{{ $row['income'] > 0 ? number_format($row['net'] / $row['income'] * 100, 1, ',', '.').'%' : '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-6">
        <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
            <input type="hidden" name="months" value="{{ $months }}">
            <h2 class="mr-auto font-semibold">{{ $currentWorkspace->type->value === 'business' ? 'DRE gerencial' : 'Demonstrativo do período' }}</h2>
            <div><label class="label">De</label><input class="input" type="date" name="from" value="{{ $from->format('Y-m-d') }}"></div>
            <div><label class="label">Até</label><input class="input" type="date" name="to" value="{{ $to->format('Y-m-d') }}"></div>
            <button class="btn-secondary">Aplicar</button>
        </form>

        <div class="grid gap-6 lg:grid-cols-2">
            <div>
                <table class="table">
                    <tbody class="divide-y divide-slate-100">
                        <tr class="bg-emerald-50 font-semibold"><td>(+) Receitas</td><td class="text-right tabular-nums">{{ money($statement['total_income']) }}</td><td></td></tr>
                        @foreach ($statement['income'] as $r)
                            <tr><td class="pl-8">{{ $r['name'] }}</td><td class="text-right tabular-nums">{{ money($r['total']) }}</td><td class="text-right text-xs text-slate-400">{{ number_format($r['percent'], 1, ',', '.') }}%</td></tr>
                        @endforeach
                        <tr class="bg-rose-50 font-semibold"><td>(−) Despesas</td><td class="text-right tabular-nums">{{ money($statement['total_expense']) }}</td><td></td></tr>
                        @foreach ($statement['expense'] as $r)
                            <tr><td class="pl-8">{{ $r['name'] }}</td><td class="text-right tabular-nums">{{ money($r['total']) }}</td><td class="text-right text-xs text-slate-400">{{ number_format($r['percent'], 1, ',', '.') }}%</td></tr>
                        @endforeach
                        <tr class="font-semibold {{ $statement['result'] < 0 ? 'bg-rose-100' : 'bg-slate-100' }}">
                            <td>(=) Resultado</td><td class="text-right"><x-amount :value="$statement['result']" /></td>
                            <td class="text-right text-xs">Margem {{ number_format($statement['margin'], 1, ',', '.') }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div>
                @if ($statement['expense']->isNotEmpty())
                    <div class="h-80">
                        <canvas data-chart="{{ json_encode([
                            'type' => 'doughnut',
                            'data' => [
                                'labels' => $statement['expense']->pluck('name'),
                                'datasets' => [['data' => $statement['expense']->pluck('total'), 'backgroundColor' => $statement['expense']->pluck('color')]],
                            ],
                            'options' => ['plugins' => ['legend' => ['position' => 'right']]],
                        ]) }}"></canvas>
                    </div>
                @else
                    <x-empty message="Sem despesas no período." />
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <h2 class="mb-4 font-semibold">Evolução do saldo consolidado</h2>
            <div class="h-64">
                <canvas data-chart="{{ json_encode([
                    'type' => 'line',
                    'data' => [
                        'labels' => array_column($evolution, 'label'),
                        'datasets' => [['label' => 'Saldo', 'data' => array_column($evolution, 'value'), 'borderColor' => '#16a34a', 'backgroundColor' => 'rgba(22,163,74,0.1)', 'fill' => true, 'tension' => 0.3]],
                    ],
                    'options' => ['plugins' => ['legend' => ['display' => false]]],
                ]) }}"></canvas>
            </div>
        </div>
        <div class="card">
            <h2 class="mb-4 font-semibold">Saldo por conta</h2>
            <ul class="divide-y divide-slate-100 text-sm">
                @foreach ($accounts as $a)
                    <li class="flex justify-between py-2"><span>{{ $a->name }}</span><x-amount :value="$a->balance" /></li>
                @endforeach
            </ul>
        </div>
    </div>
</x-layouts.app>
