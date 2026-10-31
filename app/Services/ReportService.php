<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /** Expressão SQL "AAAA-MM" compatível com SQLite, MySQL/MariaDB e PostgreSQL. */
    private function monthExpression(string $column = 'date'): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    /**
     * Receitas e despesas efetivadas por mês.
     *
     * @return Collection<int, array{month: string, label: string, income: float, expense: float, net: float}>
     */
    public function monthlyCashflow(int $months = 12, ?Carbon $end = null): Collection
    {
        $end = ($end ?? now())->copy()->endOfMonth();
        $start = $end->copy()->subMonthsNoOverflow($months - 1)->startOfMonth();
        $expr = $this->monthExpression();

        $rows = Transaction::query()
            ->paid()
            ->whereIn('type', [TransactionType::Income->value, TransactionType::Expense->value])
            ->between($start->toDateString(), $end->toDateString())
            ->selectRaw("{$expr} as ym, type, sum(amount) as total")
            ->groupByRaw("{$expr}, type")
            ->get();

        $result = collect();
        for ($m = $start->copy(); $m <= $end; $m->addMonthNoOverflow()) {
            $key = $m->format('Y-m');
            $income = (float) $rows->first(fn ($r) => $r->ym === $key && $this->typeValue($r->type) === 'income')?->total;
            $expense = (float) $rows->first(fn ($r) => $r->ym === $key && $this->typeValue($r->type) === 'expense')?->total;
            $result->push([
                'month' => $key,
                'label' => ucfirst($m->translatedFormat('M/y')),
                'income' => round($income, 2),
                'expense' => round($expense, 2),
                'net' => round($income - $expense, 2),
            ]);
        }

        return $result;
    }

    /**
     * Totais por categoria (agrupando subcategorias na categoria principal).
     *
     * @return Collection<int, array{category_id: int|null, name: string, color: string, total: float, percent: float}>
     */
    public function byCategory(string $type, string $from, string $to): Collection
    {
        $rows = Transaction::query()
            ->paid()
            ->where('type', $type)
            ->between($from, $to)
            ->selectRaw('category_id, sum(amount) as total')
            ->groupBy('category_id')
            ->get();

        $categories = Category::query()->with('parent')->get()->keyBy('id');

        $grouped = $rows->groupBy(function ($row) use ($categories) {
            $cat = $categories->get($row->category_id);

            return $cat ? ($cat->parent_id ?? $cat->id) : 0;
        })->map(function ($items, $rootId) use ($categories) {
            $root = $categories->get($rootId);

            return [
                'category_id' => $root?->id,
                'name' => $root?->name ?? 'Sem categoria',
                'color' => $root?->color ?? '#94a3b8',
                'total' => round((float) $items->sum('total'), 2),
            ];
        })->sortByDesc('total')->values();

        $sum = $grouped->sum('total') ?: 1;

        return $grouped->map(fn ($r) => $r + ['percent' => round($r['total'] / $sum * 100, 1)]);
    }

    /**
     * Orçado x realizado do mês, por categoria de despesa.
     *
     * @return Collection<int, array{category: Category, budget: float, spent: float, percent: float}>
     */
    public function budgetProgress(Carbon $month): Collection
    {
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $budgets = Budget::query()->with('category')->whereDate('month', $from->toDateString())->get();
        if ($budgets->isEmpty()) {
            return collect();
        }

        $categories = Category::query()->get(['id', 'parent_id']);
        $spentByCategory = Transaction::query()
            ->where('type', TransactionType::Expense->value)
            ->between($from->toDateString(), $to->toDateString())
            ->selectRaw('category_id, sum(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        return $budgets->map(function (Budget $budget) use ($categories, $spentByCategory) {
            $ids = $categories->where('parent_id', $budget->category_id)->pluck('id')->push($budget->category_id);
            $spent = (float) $ids->sum(fn ($id) => (float) ($spentByCategory[$id] ?? 0));
            $limit = (float) $budget->amount;

            return [
                'budget_model' => $budget,
                'category' => $budget->category,
                'budget' => $limit,
                'spent' => round($spent, 2),
                'percent' => $limit > 0 ? round($spent / $limit * 100, 1) : 0,
            ];
        })->sortByDesc('percent')->values();
    }

    /**
     * Demonstrativo de resultado simplificado (DRE) do período.
     *
     * @return array{income: Collection, expense: Collection, total_income: float, total_expense: float, result: float, margin: float}
     */
    public function incomeStatement(string $from, string $to): array
    {
        $income = $this->byCategory('income', $from, $to);
        $expense = $this->byCategory('expense', $from, $to);
        $ti = (float) $income->sum('total');
        $te = (float) $expense->sum('total');

        return [
            'income' => $income,
            'expense' => $expense,
            'total_income' => $ti,
            'total_expense' => $te,
            'result' => round($ti - $te, 2),
            'margin' => $ti > 0 ? round(($ti - $te) / $ti * 100, 1) : 0,
        ];
    }

    /** Saldo consolidado das contas ativas que entram no total. */
    public function totalBalance(): float
    {
        return round((float) Account::query()->active()->where('include_in_total', true)->withBalance()->get()->sum('balance'), 2);
    }

    /** Média mensal de despesas dos últimos N meses fechados (base para reserva de emergência). */
    public function averageMonthlyExpense(int $months = 6): float
    {
        $flow = $this->monthlyCashflow($months, now()->subMonthNoOverflow());

        return round($flow->avg('expense') ?? 0, 2);
    }

    private function typeValue(mixed $type): string
    {
        return $type instanceof TransactionType ? $type->value : (string) $type;
    }
}
