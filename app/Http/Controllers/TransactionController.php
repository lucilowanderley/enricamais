<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $month = $this->month($request);
        $query = $this->filtered($request, $month);

        $totals = (clone $query)->reorder()->selectRaw('type, sum(amount) as total')->groupBy('type')->pluck('total', 'type');

        return view('transactions.index', [
            'transactions' => $query->with(['account', 'destinationAccount', 'category.parent'])->paginate(30)->withQueryString(),
            'month' => $month,
            'income' => (float) ($totals['income'] ?? 0),
            'expense' => (float) ($totals['expense'] ?? 0),
            'accounts' => Account::query()->orderBy('name')->get(),
            'categories' => Category::query()->with('parent')->orderBy('name')->get()->sortBy('full_name'),
            'filters' => $request->only(['account', 'category', 'type', 'status', 'q']),
        ]);
    }

    public function create(Request $request): View
    {
        return view('transactions.form', $this->formData(new Transaction([
            'type' => $request->query('type', 'expense'),
            'status' => 'paid',
            'date' => now(),
            'account_id' => $request->query('account'),
        ])));
    }

    public function store(Request $request, TransactionService $service): RedirectResponse
    {
        $data = $this->validated($request);
        $installments = (int) $request->input('installments', 1);
        $data['user_id'] = $request->user()->id;

        $created = $service->create($data, max(1, min(120, $installments)));

        $message = $created->count() > 1 ? "{$created->count()} parcelas lançadas." : 'Lançamento salvo.';

        return $request->boolean('save_and_new')
            ? redirect()->route('transactions.create', ['type' => $data['type'], 'account' => $data['account_id']])->with('success', $message)
            : redirect()->route('transactions.index', ['month' => substr($data['date'], 0, 7)])->with('success', $message);
    }

    public function edit(Transaction $transaction): View
    {
        return view('transactions.form', $this->formData($transaction));
    }

    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        $data = $this->validated($request);
        if ($data['type'] === TransactionType::Transfer->value) {
            $data['category_id'] = null;
        } else {
            $data['destination_account_id'] = null;
        }
        $transaction->update($data);

        return redirect()->route('transactions.index', ['month' => $transaction->date->format('Y-m')])->with('success', 'Lançamento atualizado.');
    }

    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        $month = $transaction->date->format('Y-m');

        if ($request->boolean('all_installments') && $transaction->installment_group) {
            Transaction::query()->where('installment_group', $transaction->installment_group)
                ->where('installment_number', '>=', $transaction->installment_number)->delete();
        } else {
            $transaction->delete();
        }

        return redirect()->route('transactions.index', ['month' => $month])->with('success', 'Lançamento excluído.');
    }

    public function toggleStatus(Transaction $transaction): RedirectResponse
    {
        $transaction->update([
            'status' => $transaction->status === TransactionStatus::Paid ? TransactionStatus::Pending : TransactionStatus::Paid,
        ]);

        return back()->with('success', $transaction->status === TransactionStatus::Paid ? 'Marcado como efetivado.' : 'Marcado como pendente.');
    }

    public function export(Request $request): StreamedResponse
    {
        $month = $this->month($request);
        $rows = $this->filtered($request, $month)->with(['account', 'destinationAccount', 'category.parent'])->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM para o Excel reconhecer UTF-8
            fputcsv($out, ['Data', 'Tipo', 'Situação', 'Descrição', 'Categoria', 'Conta', 'Conta destino', 'Favorecido', 'Valor'], ';');
            foreach ($rows as $t) {
                fputcsv($out, [
                    $t->date->format('d/m/Y'),
                    $t->type->label(),
                    $t->status->label(),
                    $t->description,
                    $t->category?->full_name,
                    $t->account?->name,
                    $t->destinationAccount?->name,
                    $t->counterparty,
                    number_format($t->signedAmount() ?: (float) $t->amount, 2, ',', ''),
                ], ';');
            }
            fclose($out);
        }, 'lancamentos-'.$month->format('Y-m').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtered(Request $request, $month): Builder
    {
        $query = Transaction::query()->orderByDesc('date')->orderByDesc('id');

        if (! $request->filled('q') || $request->boolean('only_month', true)) {
            $query->between($month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString());
        }
        if ($request->filled('account')) {
            $id = (int) $request->input('account');
            $query->where(fn ($q) => $q->where('account_id', $id)->orWhere('destination_account_id', $id));
        }
        if ($request->filled('category')) {
            $id = (int) $request->input('category');
            $query->where(fn ($q) => $q->where('category_id', $id)->orWhereIn('category_id', Category::query()->where('parent_id', $id)->select('id')));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('q')) {
            $term = '%'.$request->input('q').'%';
            $query->where(fn ($q) => $q->where('description', 'like', $term)->orWhere('counterparty', 'like', $term)->orWhere('notes', 'like', $term));
        }

        return $query;
    }

    private function formData(Transaction $transaction): array
    {
        return [
            'transaction' => $transaction,
            'accounts' => Account::query()->active()->orderBy('name')->get(),
            'categories' => Category::query()->whereNull('parent_id')->with('children')->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request): array
    {
        $this->normalizeMoney($request, 'amount');

        $accountIds = Account::query()->pluck('id')->all();
        $categoryIds = Category::query()->pluck('id')->all();

        return $request->validate([
            'type' => ['required', Rule::enum(TransactionType::class)],
            'status' => ['required', Rule::enum(TransactionStatus::class)],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'account_id' => ['required', 'integer', Rule::in($accountIds)],
            'destination_account_id' => ['nullable', 'required_if:type,transfer', 'integer', Rule::in($accountIds), 'different:account_id'],
            'category_id' => ['nullable', 'integer', Rule::in($categoryIds)],
            'counterparty' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'installments' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]) + ['destination_account_id' => null, 'category_id' => null];
    }
}
