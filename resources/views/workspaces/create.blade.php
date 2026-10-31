<x-layouts.app title="Novo espaço">
    <x-page-header title="Novo espaço" subtitle="Cada espaço tem contas, categorias, orçamentos e investimentos independentes" />
    <form method="POST" action="{{ route('workspaces.store') }}" class="card max-w-2xl space-y-4" x-data="{ type: '{{ old('type', 'family') }}' }">
        @csrf
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach (['personal' => ['Pessoal', 'Suas finanças individuais'], 'family' => ['Família', 'Compartilhe com cônjuge e filhos'], 'business' => ['Empresarial', 'Fluxo de caixa e DRE da empresa']] as $value => [$label, $desc])
                <label class="cursor-pointer rounded-lg border p-4 text-sm" :class="type === '{{ $value }}' ? 'border-brand-500 bg-brand-50' : 'border-slate-200 hover:bg-slate-50'">
                    <input type="radio" name="type" value="{{ $value }}" class="sr-only" x-model="type">
                    <span class="block font-semibold">{{ $label }}</span><span class="text-slate-500">{{ $desc }}</span>
                </label>
            @endforeach
        </div>
        <div>
            <label class="label">Nome do espaço</label>
            <input class="input" name="name" value="{{ old('name') }}" required placeholder="Ex.: Família Silva, Minha Empresa Ltda">
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Moeda</label>
                <select class="input" name="currency"><option value="BRL">Real (R$)</option><option value="USD">Dólar (US$)</option><option value="EUR">Euro (€)</option></select>
            </div>
            <div x-show="type === 'business'">
                <label class="label">CNPJ (opcional)</label>
                <input class="input" name="document" value="{{ old('document') }}">
            </div>
        </div>
        <p class="text-xs text-slate-500">Criaremos categorias padrão adequadas ao tipo escolhido. Você poderá editá-las depois.</p>
        <div class="flex justify-end border-t border-slate-100 pt-4"><button class="btn-primary">Criar espaço</button></div>
    </form>
</x-layouts.app>
