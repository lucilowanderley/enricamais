@php $editing = $transaction->exists; @endphp
<x-layouts.app :title="$editing ? 'Editar lançamento' : 'Novo lançamento'">
    <x-page-header :title="$editing ? 'Editar lançamento' : 'Novo lançamento'" />

    <form method="POST" action="{{ $editing ? route('transactions.update', $transaction) : route('transactions.store') }}"
          class="card max-w-3xl space-y-5" x-data="{ type: '{{ old('type', $transaction->type?->value ?? 'expense') }}' }">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="grid grid-cols-3 gap-2 rounded-lg bg-slate-100 p-1 text-sm font-medium">
            @foreach (['expense' => ['Despesa', 'bg-rose-600'], 'income' => ['Receita', 'bg-emerald-600'], 'transfer' => ['Transferência', 'bg-slate-700']] as $value => [$label, $color])
                <label class="cursor-pointer rounded-md px-3 py-2 text-center transition" :class="type === '{{ $value }}' ? '{{ $color }} text-white shadow' : 'text-slate-600 hover:bg-white'">
                    <input type="radio" name="type" value="{{ $value }}" class="sr-only" x-model="type"> {{ $label }}
                </label>
            @endforeach
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="label">Descrição</label>
                <input class="input" name="description" value="{{ old('description', $transaction->description) }}" required placeholder="Ex.: Supermercado, Salário, Nota fiscal 123">
            </div>
            <div>
                <label class="label">Valor</label>
                <input class="input text-lg font-semibold" name="amount" inputmode="decimal" value="{{ old('amount', \App\Support\Money::input($transaction->amount)) }}" required placeholder="0,00">
            </div>
            <div>
                <label class="label">Data</label>
                <input class="input" type="date" name="date" value="{{ old('date', $transaction->date?->format('Y-m-d')) }}" required>
            </div>
            <div>
                <label class="label" x-text="type === 'transfer' ? 'Conta de origem' : 'Conta'">Conta</label>
                <select class="input" name="account_id" required>
                    @foreach ($accounts as $a)<option value="{{ $a->id }}" @selected((string) old('account_id', $transaction->account_id) === (string) $a->id)>{{ $a->name }}</option>@endforeach
                </select>
            </div>
            <div x-show="type === 'transfer'" x-cloak>
                <label class="label">Conta de destino</label>
                <select class="input" name="destination_account_id" :disabled="type !== 'transfer'">
                    <option value="">Selecione</option>
                    @foreach ($accounts as $a)<option value="{{ $a->id }}" @selected((string) old('destination_account_id', $transaction->destination_account_id) === (string) $a->id)>{{ $a->name }}</option>@endforeach
                </select>
            </div>
            <div x-show="type !== 'transfer'">
                <label class="label">Categoria</label>
                <select class="input" name="category_id" :disabled="type === 'transfer'">
                    <option value="">Sem categoria</option>
                    @foreach (['expense' => 'Despesas', 'income' => 'Receitas'] as $ct => $ctLabel)
                        <optgroup label="{{ $ctLabel }}" x-show="type === '{{ $ct }}'">
                            @foreach ($categories->filter(fn ($c) => $c->type->value === $ct) as $cat)
                                <option value="{{ $cat->id }}" @selected((string) old('category_id', $transaction->category_id) === (string) $cat->id)>{{ $cat->name }}</option>
                                @foreach ($cat->children as $child)
                                    <option value="{{ $child->id }}" @selected((string) old('category_id', $transaction->category_id) === (string) $child->id)>&nbsp;&nbsp;&nbsp;{{ $child->name }}</option>
                                @endforeach
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Situação</label>
                <select class="input" name="status">
                    @foreach (\App\Enums\TransactionStatus::options() as $v => $l)<option value="{{ $v }}" @selected(old('status', $transaction->status?->value) === $v)>{{ $l }}</option>@endforeach
                </select>
            </div>
            <div x-show="type !== 'transfer'">
                <label class="label" x-text="type === 'income' ? 'Pagador / cliente' : 'Favorecido / fornecedor'">Favorecido</label>
                <input class="input" name="counterparty" value="{{ old('counterparty', $transaction->counterparty) }}">
            </div>
            @unless ($editing)
                <div x-show="type !== 'transfer'">
                    <label class="label">Parcelas</label>
                    <input class="input" type="number" min="1" max="120" name="installments" value="{{ old('installments', 1) }}">
                    <p class="mt-1 text-xs text-slate-500">O valor total é dividido em parcelas mensais; as seguintes ficam pendentes.</p>
                </div>
            @endunless
            <div class="sm:col-span-2">
                <label class="label">Observações</label>
                <textarea class="input" name="notes" rows="2">{{ old('notes', $transaction->notes) }}</textarea>
            </div>
        </div>

        @if ($editing && $transaction->installment_group)
            <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">Parcela {{ $transaction->installment_number }} de {{ $transaction->installment_total }}. A edição altera apenas esta parcela.</p>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-4">
            <a href="{{ route('transactions.index') }}" class="btn-secondary">Cancelar</a>
            <div class="flex gap-2">
                @unless ($editing)<button name="save_and_new" value="1" class="btn-secondary">Salvar e novo</button>@endunless
                <button class="btn-primary">Salvar</button>
            </div>
        </div>
    </form>

    @if ($editing)
        <form method="POST" action="{{ route('transactions.destroy', $transaction) }}" class="mt-4 flex max-w-3xl items-center justify-end gap-4 text-sm" onsubmit="return confirm('Excluir este lançamento?')">
            @csrf @method('DELETE')
            @if ($transaction->installment_group)
                <label class="flex items-center gap-2 text-slate-600"><input type="checkbox" name="all_installments" value="1" class="rounded border-slate-300"> Excluir também as parcelas seguintes</label>
            @endif
            <button class="text-rose-600 hover:underline">Excluir lançamento</button>
        </form>
    @endif
</x-layouts.app>
