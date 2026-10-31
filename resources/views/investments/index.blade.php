<x-layouts.app title="Investimentos">
    <x-page-header title="Carteira de investimentos" :subtitle="$profile ? 'Perfil '.$profile->risk_profile->label().' · horizonte de '.$profile->horizon_years.' anos' : 'Preencha seu perfil de investidor para comparar com a alocação-alvo'">
        <x-slot:actions>
            <a href="{{ route('investments.advisor') }}" class="btn-secondary">✨ Assistente IA</a>
            @can('write')<a href="{{ route('investments.assets.create') }}" class="btn-primary">+ Novo ativo</a>@endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Patrimônio investido" :value="money($totals['market_value'])" tone="blue" :hint="'Custo: '.money($totals['invested'])" />
        <x-stat label="Resultado não realizado" :value="money($totals['unrealized'])" :tone="$totals['unrealized'] < 0 ? 'red' : 'green'" :hint="number_format($totals['unrealized_percent'], 2, ',', '.').'% sobre o custo'" />
        <x-stat label="Proventos recebidos" :value="money($totals['income'])" tone="green" hint="Dividendos, JCP, juros e rendimentos" />
        <x-stat label="Retorno total" :value="money($totals['total_return'])" :tone="$totals['total_return'] < 0 ? 'red' : 'green'" :hint="'Inclui lucro realizado: '.money($totals['realized'])" />
    </div>

    @if (! $profile)
        <div class="mt-6 flex flex-col items-start gap-3 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800 sm:flex-row sm:items-center sm:justify-between">
            <span>Defina seu perfil de risco, objetivos e alocação-alvo para receber sugestões de rebalanceamento e orientações mais precisas da IA.</span>
            <a href="{{ route('investments.profile.edit') }}" class="btn-primary btn-sm">Preencher perfil</a>
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-5">
        <div class="card lg:col-span-2">
            <h2 class="mb-4 font-semibold">Alocação por classe</h2>
            @php $current = $allocation->where('value', '>', 0); @endphp
            @if ($current->isEmpty())
                <x-empty message="Cadastre ativos e operações para ver a alocação." />
            @else
                <div class="h-60">
                    <canvas data-chart="{{ json_encode([
                        'type' => 'doughnut',
                        'data' => [
                            'labels' => $current->pluck('label')->values(),
                            'datasets' => [['data' => $current->pluck('value')->values(), 'backgroundColor' => $current->pluck('color')->values()]],
                        ],
                        'options' => ['plugins' => ['legend' => ['position' => 'bottom']]],
                    ]) }}"></canvas>
                </div>
            @endif
        </div>

        <div class="card lg:col-span-3">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold">Atual x alvo — onde aportar</h2>
                <a href="{{ route('investments.profile.edit') }}" class="text-sm text-brand-700 hover:underline">Ajustar alvo</a>
            </div>
            @if ($allocation->isEmpty())
                <x-empty message="Sem dados de alocação." />
            @else
                <div class="space-y-3">
                    @foreach ($allocation as $row)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $row['color'] }}"></span>{{ $row['label'] }}</span>
                                <span class="tabular-nums text-slate-600">
                                    {{ number_format($row['percent'], 1, ',', '.') }}%
                                    @if ($row['target'] > 0)<span class="text-slate-400">/ alvo {{ number_format($row['target'], 0) }}%</span>@endif
                                </span>
                            </div>
                            <div class="relative h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full" style="width: {{ min(100, $row['percent']) }}%; background: {{ $row['color'] }}"></div>
                                @if ($row['target'] > 0)<div class="absolute -top-1 h-4 w-0.5 bg-slate-700" style="left: {{ min(100, $row['target']) }}%"></div>@endif
                            </div>
                            @if ($row['target'] > 0 && abs($row['gap_value']) >= 1)
                                <p class="mt-0.5 text-xs {{ $row['gap_value'] > 0 ? 'text-emerald-700' : 'text-amber-700' }}">
                                    {{ $row['gap_value'] > 0 ? 'Aportar '.money($row['gap_value']).' para atingir o alvo' : 'Acima do alvo em '.money(abs($row['gap_value'])) }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="card mt-6 overflow-x-auto p-0">
        <div class="flex items-center justify-between p-5 pb-3"><h2 class="font-semibold">Ativos</h2></div>
        <table class="table">
            <thead><tr><th>Ativo</th><th>Classe</th><th class="text-right">Qtd.</th><th class="text-right">Preço médio</th><th class="text-right">Cotação</th><th class="text-right">Valor atual</th><th class="text-right">Rentab.</th><th class="text-right">% carteira</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($positions as $p)
                <tr class="hover:bg-slate-50 {{ $p['quantity'] <= 0 ? 'text-slate-400' : '' }}">
                    <td>
                        <a href="{{ route('investments.assets.show', $p['asset']) }}" class="font-medium text-slate-800 hover:underline">{{ $p['asset']->ticker ?: $p['asset']->name }}</a>
                        <span class="block text-xs text-slate-400">{{ $p['asset']->ticker ? $p['asset']->name : $p['asset']->rate_description }}</span>
                    </td>
                    <td><span class="badge" style="background: {{ $p['asset']->asset_class->color() }}22; color: {{ $p['asset']->asset_class->color() }}">{{ $p['asset']->asset_class->label() }}</span></td>
                    <td class="text-right tabular-nums">{{ rtrim(rtrim(number_format($p['quantity'], 8, ',', '.'), '0'), ',') }}</td>
                    <td class="text-right tabular-nums">{{ money($p['average_cost']) }}</td>
                    <td class="text-right tabular-nums">
                        {{ money($p['current_price']) }}
                        @if ($p['asset']->price_updated_at)<span class="block text-[11px] text-slate-400">{{ $p['asset']->price_updated_at->format('d/m') }}</span>@endif
                    </td>
                    <td class="text-right font-medium tabular-nums">{{ money($p['market_value']) }}</td>
                    <td class="text-right tabular-nums {{ $p['unrealized'] < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ number_format($p['unrealized_percent'], 2, ',', '.') }}%</td>
                    <td class="text-right tabular-nums text-slate-500">{{ $totals['market_value'] > 0 ? number_format($p['market_value'] / $totals['market_value'] * 100, 1, ',', '.').'%' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="py-10 text-center text-slate-500">Nenhum ativo cadastrado. Comece adicionando seus CDBs, Tesouro, ações, FIIs...</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($recent->isNotEmpty())
        <div class="card mt-6 overflow-x-auto p-0">
            <h2 class="p-5 pb-3 font-semibold">Últimas operações</h2>
            <table class="table">
                <tbody class="divide-y divide-slate-100">
                @foreach ($recent as $op)
                    <tr>
                        <td class="text-slate-500">{{ $op->date->format('d/m/Y') }}</td>
                        <td>{{ $op->type->label() }}</td>
                        <td><a href="{{ route('investments.assets.show', $op->asset) }}" class="hover:underline">{{ $op->asset->display_name }}</a></td>
                        <td class="text-right tabular-nums">{{ money($op->amount) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <p class="mt-6 text-xs text-slate-400">Cotações são informadas manualmente em cada ativo. Valores e rentabilidades são estimativas para acompanhamento e não constituem recomendação de investimento.</p>
</x-layouts.app>
