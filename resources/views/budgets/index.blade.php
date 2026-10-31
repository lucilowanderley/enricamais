<x-layouts.app title="Orçamentos">
    <x-page-header title="Orçamentos" subtitle="Defina limites mensais de gastos por categoria e acompanhe o realizado">
        <x-slot:actions>
            <x-month-nav :month="$month" route="budgets.index" />
            @can('write')
                <form method="POST" action="{{ route('budgets.copy') }}">@csrf<input type="hidden" name="month" value="{{ $month->format('Y-m') }}"><button class="btn-secondary">Copiar mês anterior</button></form>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat label="Total orçado" :value="money($totalBudget)" />
        <x-stat label="Total gasto" :value="money($totalSpent)" :tone="$totalSpent > $totalBudget && $totalBudget > 0 ? 'red' : 'slate'" />
        <x-stat label="Disponível" :value="money($totalBudget - $totalSpent)" :tone="$totalBudget - $totalSpent < 0 ? 'red' : 'green'" />
    </div>

    <div class="grid gap-6 lg:grid-cols-5">
        <div class="card lg:col-span-3">
            <h2 class="mb-4 font-semibold">Orçado x realizado</h2>
            @forelse ($progress as $row)
                <div class="mb-4">
                    <div class="mb-1 flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 font-medium"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $row['category']->color }}"></span>{{ $row['category']->name }}</span>
                        <span class="tabular-nums {{ $row['percent'] >= 100 ? 'text-rose-600' : 'text-slate-600' }}">{{ money($row['spent']) }} de {{ money($row['budget']) }} · {{ number_format($row['percent'], 0) }}%</span>
                    </div>
                    <x-progress :percent="$row['percent']" />
                    @if ($row['percent'] >= 100)
                        <p class="mt-1 text-xs text-rose-600">Ultrapassou em {{ money($row['spent'] - $row['budget']) }}</p>
                    @endif
                </div>
            @empty
                <x-empty message="Nenhum orçamento definido para este mês. Preencha os limites ao lado." />
            @endforelse
            <p class="mt-2 text-xs text-slate-400">Considera despesas efetivadas e pendentes do mês, incluindo subcategorias.</p>
        </div>

        <form method="POST" action="{{ route('budgets.store') }}" class="card lg:col-span-2">
            @csrf
            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
            <h2 class="mb-4 font-semibold">Limites de {{ $month->translatedFormat('F') }}</h2>
            <div class="space-y-2">
                @foreach ($categories as $cat)
                    <div class="flex items-center gap-3">
                        <label class="flex-1 text-sm">{{ $cat->name }}</label>
                        <input class="input w-36 text-right" name="amounts[{{ $cat->id }}]" inputmode="decimal" placeholder="0,00"
                               value="{{ isset($budgets[$cat->id]) ? \App\Support\Money::input($budgets[$cat->id]) : '' }}" @cannot('write') disabled @endcannot>
                    </div>
                @endforeach
            </div>
            @can('write')<button class="btn-primary mt-4 w-full">Salvar orçamento</button>@endcan
        </form>
    </div>
</x-layouts.app>
