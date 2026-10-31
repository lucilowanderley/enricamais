<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfMonth();
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $months = (int) $request->input('months', 12);
        $months = in_array($months, [6, 12, 24], true) ? $months : 12;

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'months' => $months,
            'cashflow' => $reports->monthlyCashflow($months),
            'statement' => $reports->incomeStatement($from->toDateString(), $to->toDateString()),
            'accounts' => Account::query()->active()->withBalance()->orderByDesc('balance')->get(),
            'evolution' => $this->balanceEvolution($months),
        ]);
    }

    /** Saldo consolidado ao final de cada mês. */
    private function balanceEvolution(int $months): array
    {
        $points = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $end = now()->subMonthsNoOverflow($i)->endOfMonth();
            $total = Account::query()->where('include_in_total', true)->withBalance($end->toDateString())->get()->sum('balance');
            $points[] = ['label' => ucfirst($end->translatedFormat('M/y')), 'value' => round((float) $total, 2)];
        }

        return $points;
    }
}
