<x-layouts.app :title="$asset->exists ? 'Editar ativo' : 'Novo ativo'">
    <x-page-header :title="$asset->exists ? 'Editar ativo' : 'Novo ativo'" subtitle="CDB, Tesouro Direto, ações, FIIs, ETFs, fundos, cripto, previdência..." />

    <form method="POST" action="{{ $asset->exists ? route('investments.assets.update', $asset) : route('investments.assets.store') }}" class="card max-w-3xl space-y-4">
        @csrf
        @if ($asset->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label class="label">Nome</label>
                <input class="input" name="name" value="{{ old('name', $asset->name) }}" required placeholder="Ex.: CDB Banco X 2028, Tesouro IPCA+ 2035, Itaú Unibanco PN">
            </div>
            <div>
                <label class="label">Código / ticker</label>
                <input class="input uppercase" name="ticker" value="{{ old('ticker', $asset->ticker) }}" placeholder="ITUB4, HGLG11, BTC">
            </div>
            <div>
                <label class="label">Classe</label>
                <select class="input" name="asset_class">
                    @foreach (\App\Enums\AssetClass::options() as $v => $l)<option value="{{ $v }}" @selected(old('asset_class', $asset->asset_class?->value ?? $asset->asset_class) === $v)>{{ $l }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label">Moeda</label>
                <select class="input" name="currency">
                    @foreach (['BRL', 'USD', 'EUR'] as $c)<option value="{{ $c }}" @selected(old('currency', $asset->currency) === $c)>{{ $c }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label">Instituição / corretora</label>
                <input class="input" name="institution" value="{{ old('institution', $asset->institution) }}">
            </div>
            <div>
                <label class="label">Taxa / indexador</label>
                <input class="input" name="rate_description" value="{{ old('rate_description', $asset->rate_description) }}" placeholder="110% CDI, IPCA + 6%, Pré 12%">
            </div>
            <div>
                <label class="label">Vencimento</label>
                <input class="input" type="date" name="maturity_date" value="{{ old('maturity_date', $asset->maturity_date?->format('Y-m-d')) }}">
            </div>
            <div>
                <label class="label">Cotação / preço unitário atual</label>
                <input class="input" name="current_price" inputmode="decimal" value="{{ old('current_price', $asset->current_price !== null ? rtrim(rtrim(number_format((float) $asset->current_price, 8, ',', '.'), '0'), ',') : '') }}">
                <p class="mt-1 text-xs text-slate-500">Para renda fixa, use o valor atual da cota/unidade ou registre quantidade 1 com o valor aplicado.</p>
            </div>
            <div class="sm:col-span-3">
                <label class="label">Observações</label>
                <textarea class="input" name="notes" rows="2">{{ old('notes', $asset->notes) }}</textarea>
            </div>
        </div>
        <div class="flex items-center justify-between border-t border-slate-100 pt-4">
            <a href="{{ $asset->exists ? route('investments.assets.show', $asset) : route('investments.index') }}" class="btn-secondary">Cancelar</a>
            <button class="btn-primary">Salvar</button>
        </div>
    </form>
</x-layouts.app>
