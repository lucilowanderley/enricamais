<?php

namespace App\Http\Controllers\Investments;

use App\Enums\AssetClass;
use App\Enums\RiskProfile;
use App\Http\Controllers\Controller;
use App\Models\InvestorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $profile = InvestorProfile::query()->first() ?? new InvestorProfile([
            'risk_profile' => RiskProfile::Moderate,
            'horizon_years' => 5,
            'emergency_fund_months' => 6,
            'monthly_contribution' => 0,
        ]);

        $suggestions = collect(RiskProfile::cases())->mapWithKeys(fn ($p) => [$p->value => AssetClass::suggestedAllocation($p)]);

        return view('investments.profile', compact('profile', 'suggestions'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->normalizeMoney($request, 'monthly_contribution');

        $classRules = collect(AssetClass::cases())->mapWithKeys(fn ($c) => ["target_allocation.{$c->value}" => ['nullable', 'numeric', 'min:0', 'max:100']])->all();

        $data = $request->validate([
            'risk_profile' => ['required', Rule::enum(RiskProfile::class)],
            'horizon_years' => ['required', 'integer', 'min:1', 'max:60'],
            'monthly_contribution' => ['nullable', 'numeric', 'min:0'],
            'emergency_fund_months' => ['required', 'integer', 'min:0', 'max:36'],
            'objectives' => ['nullable', 'string', 'max:3000'],
        ] + $classRules);

        $allocation = collect($request->input('target_allocation', []))
            ->only(collect(AssetClass::cases())->map->value->all())
            ->map(fn ($v) => (float) str_replace(',', '.', (string) $v))
            ->filter(fn ($v) => $v > 0);

        if ($allocation->isNotEmpty() && abs($allocation->sum() - 100) > 0.5) {
            return back()->withInput()->withErrors(['target_allocation' => 'A alocação-alvo deve somar 100% (soma atual: '.round($allocation->sum(), 1).'%).']);
        }

        $data['target_allocation'] = $allocation->isNotEmpty() ? $allocation->all() : null;
        $data['monthly_contribution'] ??= 0;

        InvestorProfile::query()->updateOrCreate([], $data);

        return redirect()->route('investments.index')->with('success', 'Perfil de investidor salvo.');
    }
}
