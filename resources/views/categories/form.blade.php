<x-layouts.app :title="$category->exists ? 'Editar categoria' : 'Nova categoria'">
    <x-page-header :title="$category->exists ? 'Editar categoria' : 'Nova categoria'" />

    <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}" class="card max-w-xl space-y-4">
        @csrf
        @if ($category->exists) @method('PUT') @endif
        <div>
            <label class="label">Nome</label>
            <input class="input" name="name" value="{{ old('name', $category->name) }}" required>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Tipo</label>
                <select class="input" name="type">
                    @foreach (\App\Enums\CategoryType::options() as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $category->type?->value ?? $category->type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Categoria principal (opcional)</label>
                <select class="input" name="parent_id">
                    <option value="">— Nenhuma —</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $parent->id)>{{ $parent->name }} ({{ $parent->type->label() }})</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="label">Cor</label>
            <input type="color" class="h-10 w-20 rounded border border-slate-300" name="color" value="{{ old('color', $category->color) }}">
        </div>
        <div class="flex items-center justify-between border-t border-slate-100 pt-4">
            <a href="{{ route('categories.index') }}" class="btn-secondary">Cancelar</a>
            <button class="btn-primary">Salvar</button>
        </div>
    </form>

    @if ($category->exists)
        <form method="POST" action="{{ route('categories.destroy', $category) }}" class="mt-4 max-w-xl text-right" onsubmit="return confirm('Excluir esta categoria? Os lançamentos ficarão sem categoria.')">
            @csrf @method('DELETE')
            <button class="text-sm text-rose-600 hover:underline">Excluir categoria</button>
        </form>
    @endif
</x-layouts.app>
