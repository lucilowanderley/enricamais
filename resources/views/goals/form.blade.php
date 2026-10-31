<x-layouts.app :title="$goal->exists ? 'Editar meta' : 'Nova meta'">
    <x-page-header :title="$goal->exists ? 'Editar meta' : 'Nova meta'" />
    <form method="POST" action="{{ $goal->exists ? route('goals.update', $goal) : route('goals.store') }}" class="card max-w-2xl space-y-4">
        @csrf
        @if ($goal->exists) @method('PUT') @endif
        <div>
            <label class="label">Nome</label>
            <input class="input" name="name" value="{{ old('name', $goal->name) }}" required placeholder="Ex.: Reserva de emergência">
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="label">Valor alvo</label>
                <input class="input" name="target_amount" inputmode="decimal" value="{{ old('target_amount', \App\Support\Money::input($goal->target_amount)) }}" required>
            </div>
            <div>
                <label class="label">Já acumulado</label>
                <input class="input" name="current_amount" inputmode="decimal" value="{{ old('current_amount', \App\Support\Money::input($goal->current_amount ?? 0)) }}">
            </div>
            <div>
                <label class="label">Prazo</label>
                <input class="input" type="date" name="target_date" value="{{ old('target_date', $goal->target_date?->format('Y-m-d')) }}">
            </div>
        </div>
        <div>
            <label class="label">Cor</label>
            <input type="color" class="h-10 w-20 rounded border border-slate-300" name="color" value="{{ old('color', $goal->color) }}">
        </div>
        <div>
            <label class="label">Observações</label>
            <textarea class="input" name="notes" rows="2">{{ old('notes', $goal->notes) }}</textarea>
        </div>
        <div class="flex items-center justify-between border-t border-slate-100 pt-4">
            <a href="{{ route('goals.index') }}" class="btn-secondary">Cancelar</a>
            <button class="btn-primary">Salvar</button>
        </div>
    </form>
    @if ($goal->exists)
        <form method="POST" action="{{ route('goals.destroy', $goal) }}" class="mt-4 max-w-2xl text-right" onsubmit="return confirm('Excluir esta meta?')">
            @csrf @method('DELETE')<button class="text-sm text-rose-600 hover:underline">Excluir meta</button>
        </form>
    @endif
</x-layouts.app>
