<?php

namespace App\Http\Controllers\Investments;

use App\Enums\InvestmentTransactionType;
use App\Http\Controllers\Controller;
use App\Models\InvestmentAsset;
use App\Models\InvestmentTransaction;
use App\Services\PortfolioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OperationController extends Controller
{
    public function store(Request $request, InvestmentAsset $asset, PortfolioService $portfolio): RedirectResponse
    {
        $this->normalizeMoney($request, 'quantity', 'unit_price', 'fees', 'amount');

        $data = $request->validate([
            'type' => ['required', Rule::enum(InvestmentTransactionType::class)],
            'date' => ['required', 'date'],
            'quantity' => ['nullable', 'required_unless:type,income', 'numeric', 'gt:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'fees' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $type = InvestmentTransactionType::from($data['type']);
        $data['quantity'] = $type === InvestmentTransactionType::Income ? 0 : (float) $data['quantity'];
        $data['unit_price'] = (float) ($data['unit_price'] ?? 0);
        $data['fees'] = (float) ($data['fees'] ?? 0);
        $data['amount'] = (float) ($data['amount'] ?? 0) ?: round($data['quantity'] * $data['unit_price'], 2);

        if ($data['amount'] <= 0) {
            return back()->withInput()->withErrors(['amount' => 'Informe o valor total ou quantidade e preço unitário.']);
        }
        if ($data['unit_price'] <= 0 && $data['quantity'] > 0) {
            $data['unit_price'] = $data['amount'] / $data['quantity'];
        }
        if ($type === InvestmentTransactionType::Sell) {
            $available = $portfolio->position($asset->load('operations'))['quantity'];
            if ($data['quantity'] > $available + 1e-8) {
                return back()->withInput()->withErrors(['quantity' => "Quantidade maior que a posição disponível ({$available})."]);
            }
        }

        $asset->operations()->create($data);

        return redirect()->route('investments.assets.show', $asset)->with('success', 'Operação registrada.');
    }

    public function destroy(InvestmentAsset $asset, InvestmentTransaction $operation): RedirectResponse
    {
        abort_unless($operation->investment_asset_id === $asset->id, 404);
        $operation->delete();

        return back()->with('success', 'Operação excluída.');
    }
}
