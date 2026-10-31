<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Models\Account;
use App\Models\Goal;
use App\Models\InvestorProfile;
use App\Models\Transaction;
use App\Services\PortfolioService;
use App\Services\ReportService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ReportService $reports, PortfolioService $portfolio): View
    {
        $from = now()->startOfMonth()->toDateString();
        $to = now()->endOfMonth()->toDateString();

        $cashflow = $reports->monthlyCashflow(6);
        $current = $cashflow->last();

        $accounts = Account::query()->active()->withBalance()->orderBy('name')->get();

        $upcoming = Transaction::query()
            ->with(['account', 'category'])
            ->where('status', TransactionStatus::Pending->value)
            ->where('date', '<=', now()->addDays(15)->toDateString())
            ->orderBy('date')
            ->limit(8)
            ->get();

        $recent = Transaction::query()->with(['account', 'category', 'destinationAccount'])
            ->orderByDesc('date')->orderByDesc('id')->limit(8)->get();

        return view('dashboard', [
            'totalBalance' => $reports->totalBalance(),
            'monthIncome' => $current['income'] ?? 0,
            'monthExpense' => $current['expense'] ?? 0,
            'cashflow' => $cashflow,
            'expensesByCategory' => $reports->byCategory('expense', $from, $to),
            'budgets' => $reports->budgetProgress(now())->take(5),
            'accounts' => $accounts,
            'upcoming' => $upcoming,
            'recent' => $recent,
            'goals' => Goal::query()->orderBy('target_date')->limit(4)->get(),
            'portfolio' => $portfolio->summary(InvestorProfile::query()->first())['totals'],
        ]);
    }
}
