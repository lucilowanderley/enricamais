<x-layouts.app :title="$account->exists ? 'Editar conta' : 'Nova conta'">
    <x-page-header :title="$account->exists ? 'Editar conta' : 'Nova conta'" />

    <form method="POST" action="{{ $account->exists ? route('accounts.update', $account) : route('accounts.store') }}" class="card max-w-2xl space-y-4">
        @csrf
        @if ($account->exists) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="label">Nome</label>
                <input class="input" name="name" value="{{ old('name', $account->name) }}" required placeholder="Ex.: Nubank, Itaú PJ, Carteira">
            </div>
            <div>
                <label class="label">Tipo</label>
                <select class="input" name="type">
                    @foreach (\App\Enums\AccountType::options() as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $account->type?->value) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Instituição</label>
                <input class="input" name="institution" value="{{ old('institution', $account->institution) }}" placeholder="Banco / corretora">
            </div>
            <div>
                <label class="label">Saldo inicial</label>
                <input class="input" name="initial_balance" inputmode="decimal" value="{{ old('initial_balance', \App\Support\Money::input($account->initial_balance ?? 0)) }}">
                <p class="mt-1 text-xs text-slate-500">Para cartão de crédito, informe a fatura em aberto como valor negativo.</p>
            </div>
            <div>
                <label class="label">Limite (cartão de crédito)</label>
                <input class="input" name="credit_limit" inputmode="decimal" value="{{ old('credit_limit', \App\Support\Money::input($account->credit_limit)) }}">
            </div>
            <div>
                <label class="label">Cor</label>
                <input type="color" class="h-10 w-20 rounded border border-slate-300" name="color" value="{{ old('color', $account->color) }}">
            </div>
            <div class="flex flex-col justify-end gap-2 text-sm">
                <label class="flex items-center gap-2"><input type="checkbox" name="include_in_total" value="1" class="rounded border-slate-300" @checked(old('include_in_total', $account->include_in_total))> Somar no saldo consolidado</label>
                @if ($account->exists)
                    <label class="flex items-center gap-2"><input type="checkbox" name="archived" value="1" class="rounded border-slate-300" @checked($account->archived_at)> Arquivar conta</label>
                @endif
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-slate-100 pt-4">
            <a href="{{ route('accounts.index') }}" class="btn-secondary">Cancelar</a>
            <button class="btn-primary">Salvar</button>
        </div>
    </form>

    @if ($account->exists)
        <form method="POST" action="{{ route('accounts.destroy', $account) }}" class="mt-4 max-w-2xl text-right" onsubmit="return confirm('Excluir esta conta?')">
            @csrf @method('DELETE')
            <button class="text-sm text-rose-600 hover:underline">Excluir conta</button>
        </form>
    @endif
</x-layouts.app>
