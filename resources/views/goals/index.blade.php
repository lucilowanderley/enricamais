<x-layouts.app title="Metas">
    <x-page-header title="Metas financeiras" subtitle="Reserva de emergência, viagens, troca de carro, aposentadoria...">
        <x-slot:actions>@can('write')<a href="{{ route('goals.create') }}" class="btn-primary">+ Nova meta</a>@endcan</x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($goals as $goal)
            <div class="card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-semibold">{{ $goal->name }}</p>
                        <p class="text-xs text-slate-500">{{ $goal->target_date ? 'Até '.$goal->target_date->format('d/m/Y') : 'Sem prazo definido' }}</p>
                    </div>
                    @can('write')<a href="{{ route('goals.edit', $goal) }}" class="text-xs text-brand-700 hover:underline">Editar</a>@endcan
                </div>
                <p class="mt-4 text-xl font-semibold">{{ money($goal->current_amount) }} <span class="text-sm font-normal text-slate-500">de {{ money($goal->target_amount) }}</span></p>
                <div class="mt-2"><x-progress :percent="$goal->progress()" :color="$goal->color" /></div>
                <p class="mt-2 text-xs text-slate-500">
                    {{ number_format($goal->progress(), 1, ',', '.') }}% concluído
                    @if ($goal->monthlyNeeded() !== null && $goal->progress() < 100) · guardar {{ money($goal->monthlyNeeded()) }}/mês @endif
                </p>
                @can('write')
                    <form method="POST" action="{{ route('goals.contribute', $goal) }}" class="mt-4 flex gap-2">
                        @csrf
                        <input class="input" name="value" inputmode="decimal" placeholder="Valor (use - para retirar)" required>
                        <button class="btn-secondary">Aportar</button>
                    </form>
                @endcan
            </div>
        @empty
            <x-empty message="Nenhuma meta criada ainda." />
        @endforelse
    </div>
</x-layouts.app>
