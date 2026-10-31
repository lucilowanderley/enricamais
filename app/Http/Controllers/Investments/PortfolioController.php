<?php

namespace App\Http\Controllers\Investments;

use App\Http\Controllers\Controller;
use App\Models\InvestmentTransaction;
use App\Models\InvestorProfile;
use App\Services\PortfolioService;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function __invoke(PortfolioService $portfolio): View
    {
        $profile = InvestorProfile::query()->first();
        $summary = $portfolio->summary($profile);

        return view('investments.index', $summary + [
            'profile' => $profile,
            'recent' => InvestmentTransaction::query()->with('asset')->orderByDesc('date')->orderByDesc('id')->limit(10)->get(),
        ]);
    }
}
