<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Models\Budget;
use App\Models\Category;
use App\Services\ReportService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        $month = $this->month($request)->startOfMonth();

        $progress = $reports->budgetProgress($month);
        $categories = Category::query()->whereNull('parent_id')->where('type', CategoryType::Expense->value)->orderBy('name')->get();
        $budgets = Budget::query()->whereDate('month', $month->toDateString())->pluck('amount', 'category_id');

        return view('budgets.index', [
            'month' => $month,
            'progress' => $progress,
            'categories' => $categories,
            'budgets' => $budgets,
            'totalBudget' => $progress->sum('budget'),
            'totalSpent' => $progress->sum('spent'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $month = $this->month($request)->startOfMonth()->toDateString();
        $validIds = Category::query()->whereNull('parent_id')->where('type', CategoryType::Expense->value)->pluck('id')->all();

        foreach ((array) $request->input('amounts', []) as $categoryId => $raw) {
            if (! in_array((int) $categoryId, $validIds, true)) {
                continue;
            }
            $amount = Money::parse($raw);
            if ($amount === null || ! is_numeric($amount) || (float) $amount <= 0) {
                Budget::query()->where('category_id', $categoryId)->whereDate('month', $month)->delete();

                continue;
            }
            $existing = Budget::query()->where('category_id', $categoryId)->whereDate('month', $month)->first();
            $existing
                ? $existing->update(['amount' => $amount])
                : Budget::create(['category_id' => $categoryId, 'month' => $month, 'amount' => $amount]);
        }

        return redirect()->route('budgets.index', ['month' => substr($month, 0, 7)])->with('success', 'Orçamento salvo.');
    }

    public function copyPrevious(Request $request): RedirectResponse
    {
        $month = $this->month($request)->startOfMonth();
        $previous = $month->copy()->subMonthNoOverflow()->toDateString();

        $copied = 0;
        foreach (Budget::query()->whereDate('month', $previous)->get() as $budget) {
            $exists = Budget::query()->where('category_id', $budget->category_id)->whereDate('month', $month->toDateString())->exists();
            if (! $exists) {
                Budget::create(['category_id' => $budget->category_id, 'month' => $month->toDateString(), 'amount' => $budget->amount]);
                $copied++;
            }
        }

        return redirect()->route('budgets.index', ['month' => $month->format('Y-m')])
            ->with('success', $copied ? "{$copied} orçamento(s) copiados do mês anterior." : 'Nada para copiar do mês anterior.');
    }
}
