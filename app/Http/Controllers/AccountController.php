<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(): View
    {
        $accounts = Account::query()->withBalance()->orderByRaw('archived_at is not null')->orderBy('name')->get();

        return view('accounts.index', [
            'accounts' => $accounts,
            'total' => $accounts->whereNull('archived_at')->where('include_in_total', true)->sum('balance'),
        ]);
    }

    public function create(): View
    {
        return view('accounts.form', ['account' => new Account(['color' => '#16a34a', 'include_in_total' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Account::create($this->validated($request));

        return redirect()->route('accounts.index')->with('success', 'Conta criada.');
    }

    public function show(Request $request, Account $account): View
    {
        $month = $this->month($request);
        $from = $month->copy()->startOfMonth()->toDateString();
        $to = $month->copy()->endOfMonth()->toDateString();

        $transactions = Transaction::query()
            ->with(['category', 'account', 'destinationAccount'])
            ->where(fn ($q) => $q->where('account_id', $account->id)->orWhere('destination_account_id', $account->id))
            ->between($from, $to)
            ->orderBy('date')->orderBy('id')
            ->get();

        $opening = (float) (Account::query()->withBalance(Carbon::parse($from)->subDay()->toDateString())->find($account->id)?->balance ?? 0);
        $balance = (float) Account::query()->withBalance()->find($account->id)->balance;

        return view('accounts.show', compact('account', 'transactions', 'opening', 'balance', 'month'));
    }

    public function edit(Account $account): View
    {
        return view('accounts.form', compact('account'));
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        $data = $this->validated($request);
        $data['archived_at'] = $request->boolean('archived') ? ($account->archived_at ?? now()) : null;
        $account->update($data);

        return redirect()->route('accounts.index')->with('success', 'Conta atualizada.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        if ($account->transactions()->exists() || $account->incomingTransfers()->exists()) {
            return back()->withErrors(['account' => 'Esta conta possui lançamentos. Arquive-a em vez de excluir.']);
        }

        $account->delete();

        return redirect()->route('accounts.index')->with('success', 'Conta excluída.');
    }

    private function validated(Request $request): array
    {
        $this->normalizeMoney($request, 'initial_balance', 'credit_limit');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(AccountType::class)],
            'institution' => ['nullable', 'string', 'max:120'],
            'initial_balance' => ['nullable', 'numeric', 'between:-999999999999,999999999999'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
        $data['initial_balance'] ??= 0;
        $data['include_in_total'] = $request->boolean('include_in_total');

        return $data;
    }
}
