<?php

namespace App\Http\Controllers\Investments;

use App\Enums\AssetClass;
use App\Http\Controllers\Controller;
use App\Models\InvestmentAsset;
use App\Services\PortfolioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function create(): View
    {
        return view('investments.assets.form', ['asset' => new InvestmentAsset(['currency' => 'BRL', 'asset_class' => 'fixed_income'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $asset = InvestmentAsset::create($this->validated($request));

        return redirect()->route('investments.assets.show', $asset)->with('success', 'Ativo cadastrado. Agora registre a primeira compra/aporte.');
    }

    public function show(InvestmentAsset $asset, PortfolioService $portfolio): View
    {
        $asset->load('operations');

        return view('investments.assets.show', [
            'asset' => $asset,
            'position' => $portfolio->position($asset),
        ]);
    }

    public function edit(InvestmentAsset $asset): View
    {
        return view('investments.assets.form', compact('asset'));
    }

    public function update(Request $request, InvestmentAsset $asset): RedirectResponse
    {
        $asset->update($this->validated($request, $asset));

        return redirect()->route('investments.assets.show', $asset)->with('success', 'Ativo atualizado.');
    }

    public function updatePrice(Request $request, InvestmentAsset $asset): RedirectResponse
    {
        $this->normalizeMoney($request, 'current_price');
        $data = $request->validate(['current_price' => ['required', 'numeric', 'min:0']]);
        $asset->update(['current_price' => $data['current_price'], 'price_updated_at' => now()]);

        return back()->with('success', 'Cotação atualizada.');
    }

    public function destroy(InvestmentAsset $asset): RedirectResponse
    {
        $asset->delete();

        return redirect()->route('investments.index')->with('success', 'Ativo excluído.');
    }

    private function validated(Request $request, ?InvestmentAsset $asset = null): array
    {
        $this->normalizeMoney($request, 'current_price');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'ticker' => ['nullable', 'string', 'max:30'],
            'asset_class' => ['required', Rule::enum(AssetClass::class)],
            'currency' => ['required', Rule::in(['BRL', 'USD', 'EUR'])],
            'institution' => ['nullable', 'string', 'max:120'],
            'rate_description' => ['nullable', 'string', 'max:120'],
            'maturity_date' => ['nullable', 'date'],
            'current_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (isset($data['ticker'])) {
            $data['ticker'] = mb_strtoupper(trim($data['ticker']));
        }
        if (($data['current_price'] ?? null) !== null && (string) $data['current_price'] !== (string) $asset?->current_price) {
            $data['price_updated_at'] = now();
        }

        return $data;
    }
}
