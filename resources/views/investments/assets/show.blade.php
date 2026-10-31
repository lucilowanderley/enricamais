@php $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 8, ',', '.'), '0'), ','); @endphp
<x-layouts.app :title="$asset->display_name">
    <x-page-header :title="$asset->display_name" :subtitle="$asset->asset_class->label().($asset->institution ? ' · '.$asset->institution : '').($asset->rate_description ? ' · '.$asset->rate_description : '').($asset->maturity_date ? ' · vence em '.$asset->maturity_date->format('d/m/Y') : '')">
        <x-slot:actions>
            <a href="{{ route('investments.index') }}" class="btn-secondary">Voltar</a>
            @can('write')<a href="{{ route('investments.assets.edit', $asset) }}" class="btn-secondary">Editar</a>@endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Quantidade" :value="$fmtQty($position['quantity'])" :hint="'Preço médio: '.money($position['average_cost'])" />
        <x-stat label="Valor atual" :value="money($position['market_value'])" tone="blue" :hint="'Custo: '.money($position['invested'])" />
        <x-stat label="Resultado não realizado" :value="money($position['unrealized'])" :tone="$position['unrealized'] < 0 ? 'red' : 'green'" :hint="number_format($position['unrealized_percent'], 2, ',', '.').'%'" />
        <x-stat label="Proventos + lucro realizado" :value="money($position['income'] + $position['realized'])" tone="green" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        @can('write')
        <div class="space-y-6">
            <form method="POST" action="{{ route('investments.operations.store', $asset) }}" class="card space-y-3" x-data="{ type: '{{ old('type', 'buy') }}' }">
                @csrf
                <h2 class="font-semibold">Registrar operação</h2>
                <select class="input" name="type" x-model="type">
                    @foreach (\App\Enums\InvestmentTransactionType::options() as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                </select>
                <input class="input" type="date" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required>
                <div class="grid grid-cols-2 gap-2" x-show="type !== 'income'">
                    <div><label class="label">Quantidade</label><input class="input" name="quantity" inputmode="decimal" value="{{ old('quantity') }}"></div>
                    <div><label class="label">Preço unitário</label><input class="input" name="unit_price" inputmode="decimal" value="{{ old('unit_price') }}"></div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="label" x-text="type === 'income' ? 'Valor recebido' : 'Valor total (opcional)'">Valor</label><input class="input" name="amount" inputmode="decimal" value="{{ old('amount') }}"></div>
                    <div><label class="label">Taxas / IR</label><input class="input" name="fees" inputmode="decimal" value="{{ old('fees') }}"></div>
                </div>
                <input class="input" name="notes" placeholder="Observação (opcional)" value="{{ old('notes') }}">
                <button class="btn-primary w-full">Registrar</button>
            </form>

            <form method="POST" action="{{ route('investments.assets.price', $asset) }}" class="card space-y-3">
                @csrf @method('PATCH')
                <h2 class="font-semibold">Atualizar cotação</h2>
                <div class="flex gap-2">
                    <input class="input" name="current_price" inputmode="decimal" placeholder="Preço atual" required>
                    <button class="btn-secondary">Atualizar</button>
                </div>
                <p class="text-xs text-slate-500">Última atualização: {{ $asset->price_updated_at?->format('d/m/Y H:i') ?? 'nunca' }}</p>
            </form>
        </div>
        @endcan

        <div class="card overflow-x-auto p-0 {{ workspace()->canWrite() ? 'lg:col-span-2' : 'lg:col-span-3' }}">
            <h2 class="p-5 pb-3 font-semibold">Histórico de operações</h2>
            <table class="table">
                <thead><tr><th>Data</th><th>Tipo</th><th class="text-right">Qtd.</th><th class="text-right">Preço</th><th class="text-right">Taxas</th><th class="text-right">Total</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($asset->operations->sortByDesc('date') as $op)
                    <tr>
                        <td>{{ $op->date->format('d/m/Y') }}</td>
                        <td><span class="badge {{ ['buy' => 'bg-sky-50 text-sky-700', 'sell' => 'bg-amber-50 text-amber-700', 'income' => 'bg-emerald-50 text-emerald-700'][$op->type->value] }}">{{ $op->type->label() }}</span></td>
                        <td class="text-right tabular-nums">{{ $op->type->value === 'income' ? '—' : $fmtQty($op->quantity) }}</td>
                        <td class="text-right tabular-nums">{{ $op->type->value === 'income' ? '—' : money($op->unit_price) }}</td>
                        <td class="text-right tabular-nums">{{ money($op->fees) }}</td>
                        <td class="text-right font-medium tabular-nums">{{ money($op->amount) }}</td>
                        <td class="text-right">
                            @can('write')
                                <form method="POST" action="{{ route('investments.operations.destroy', [$asset, $op]) }}" onsubmit="return confirm('Excluir operação?')">@csrf @method('DELETE')<button class="text-xs text-rose-600 hover:underline">Excluir</button></form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-slate-500">Nenhuma operação registrada.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('write')
        <form method="POST" action="{{ route('investments.assets.destroy', $asset) }}" class="mt-6 text-right" onsubmit="return confirm('Excluir o ativo e todo o histórico?')">
            @csrf @method('DELETE')<button class="text-sm text-rose-600 hover:underline">Excluir ativo</button>
        </form>
    @endcan
</x-layouts.app>
