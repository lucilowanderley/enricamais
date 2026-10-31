<x-layouts.app title="Categorias">
    <x-page-header title="Categorias" subtitle="Organize receitas e despesas em categorias e subcategorias" />

    <div class="grid gap-6 lg:grid-cols-2">
        @foreach (['expense' => ['Despesas', $expense], 'income' => ['Receitas', $income]] as $type => [$label, $list])
            <div class="card">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-semibold">{{ $label }}</h2>
                    @can('write')<a href="{{ route('categories.create', ['type' => $type]) }}" class="btn-secondary btn-sm">+ Categoria</a>@endcan
                </div>
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach ($list as $cat)
                        <li class="py-2">
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-2 font-medium"><span class="h-3 w-3 rounded-full" style="background: {{ $cat->color }}"></span>{{ $cat->name }}</span>
                                @can('write')
                                <span class="flex gap-3 text-xs">
                                    <a href="{{ route('categories.create', ['type' => $type, 'parent' => $cat->id]) }}" class="text-brand-700 hover:underline">+ sub</a>
                                    <a href="{{ route('categories.edit', $cat) }}" class="text-slate-500 hover:underline">editar</a>
                                </span>
                                @endcan
                            </div>
                            @if ($cat->children->isNotEmpty())
                                <div class="mt-1 flex flex-wrap gap-1.5 pl-5">
                                    @foreach ($cat->children as $child)
                                        @can('write')
                                            <a href="{{ route('categories.edit', $child) }}" class="badge bg-slate-100 text-slate-600 hover:bg-slate-200">{{ $child->name }}</a>
                                        @else
                                            <span class="badge bg-slate-100 text-slate-600">{{ $child->name }}</span>
                                        @endcan
                                    @endforeach
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</x-layouts.app>
