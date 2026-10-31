<?php

namespace App\Services;

use App\Enums\AssetClass;
use App\Enums\InvestmentTransactionType;
use App\Models\InvestmentAsset;
use App\Models\InvestorProfile;
use Illuminate\Support\Collection;

class PortfolioService
{
    /**
     * Calcula a posição de um ativo pelo método do custo médio ponderado.
     *
     * @return array{quantity: float, average_cost: float, invested: float, current_price: float, market_value: float, unrealized: float, unrealized_percent: float, realized: float, income: float, total_return: float}
     */
    public function position(InvestmentAsset $asset): array
    {
        $quantity = 0.0;
        $cost = 0.0;      // custo total da posição em aberto
        $realized = 0.0;  // lucro/prejuízo realizado nas vendas
        $income = 0.0;    // proventos / rendimentos recebidos

        foreach ($asset->operations as $op) {
            $qty = (float) $op->quantity;
            $gross = (float) $op->amount ?: $qty * (float) $op->unit_price;
            $fees = (float) $op->fees;

            switch ($op->type) {
                case InvestmentTransactionType::Buy:
                    $quantity += $qty;
                    $cost += $gross + $fees;
                    break;
                case InvestmentTransactionType::Sell:
                    if ($quantity <= 0) {
                        break;
                    }
                    $sold = min($qty, $quantity);
                    $avg = $cost / $quantity;
                    $realized += ($gross - $fees) - $avg * $sold;
                    $cost -= $avg * $sold;
                    $quantity -= $sold;
                    break;
                case InvestmentTransactionType::Income:
                    $income += $gross - $fees;
                    break;
            }
        }

        if ($quantity < 1e-9) {
            $quantity = 0.0;
            $cost = 0.0;
        }

        $average = $quantity > 0 ? $cost / $quantity : 0.0;
        $price = $asset->current_price !== null ? (float) $asset->current_price : $average;
        $market = $quantity * $price;
        $unrealized = $market - $cost;

        return [
            'quantity' => round($quantity, 8),
            'average_cost' => round($average, 6),
            'invested' => round($cost, 2),
            'current_price' => round($price, 6),
            'market_value' => round($market, 2),
            'unrealized' => round($unrealized, 2),
            'unrealized_percent' => $cost > 0 ? round($unrealized / $cost * 100, 2) : 0.0,
            'realized' => round($realized, 2),
            'income' => round($income, 2),
            'total_return' => round($unrealized + $realized + $income, 2),
        ];
    }

    /**
     * Resumo completo da carteira do espaço ativo.
     *
     * @return array{positions: Collection, totals: array<string, float>, allocation: Collection}
     */
    public function summary(?InvestorProfile $profile = null): array
    {
        $assets = InvestmentAsset::query()->with('operations')->orderBy('asset_class')->orderBy('name')->get();

        $positions = $assets->map(fn (InvestmentAsset $a) => ['asset' => $a] + $this->position($a));

        $totals = [
            'invested' => round($positions->sum('invested'), 2),
            'market_value' => round($positions->sum('market_value'), 2),
            'unrealized' => round($positions->sum('unrealized'), 2),
            'realized' => round($positions->sum('realized'), 2),
            'income' => round($positions->sum('income'), 2),
        ];
        $totals['total_return'] = round($totals['unrealized'] + $totals['realized'] + $totals['income'], 2);
        $totals['unrealized_percent'] = $totals['invested'] > 0 ? round($totals['unrealized'] / $totals['invested'] * 100, 2) : 0.0;

        $target = $profile?->targetAllocation() ?? [];
        $marketTotal = $totals['market_value'] ?: 0;

        $allocation = collect(AssetClass::cases())->map(function (AssetClass $class) use ($positions, $marketTotal, $target) {
            $value = (float) $positions->filter(fn ($p) => $p['asset']->asset_class === $class)->sum('market_value');
            $percent = $marketTotal > 0 ? round($value / $marketTotal * 100, 2) : 0.0;
            $targetPercent = (float) ($target[$class->value] ?? 0);

            return [
                'class' => $class,
                'label' => $class->label(),
                'color' => $class->color(),
                'value' => round($value, 2),
                'percent' => $percent,
                'target' => $targetPercent,
                'gap' => round($targetPercent - $percent, 2),
                'gap_value' => round($marketTotal * $targetPercent / 100 - $value, 2),
            ];
        })->filter(fn ($row) => $row['value'] > 0 || $row['target'] > 0)->values();

        return compact('positions', 'totals', 'allocation');
    }
}
