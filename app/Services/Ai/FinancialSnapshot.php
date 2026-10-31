<?php

namespace App\Services\Ai;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Goal;
use App\Models\InvestorProfile;
use App\Services\PortfolioService;
use App\Services\ReportService;
use App\Support\WorkspaceContext;

/**
 * Monta um retrato resumido e anonimizado das finanças do espaço ativo,
 * enviado como contexto para o assistente de IA. Não inclui nomes de pessoas,
 * descrições de lançamentos nem dados de documentos.
 */
class FinancialSnapshot
{
    public function __construct(
        private readonly WorkspaceContext $context,
        private readonly ReportService $reports,
        private readonly PortfolioService $portfolio,
    ) {}

    /** @return array<string, mixed> */
    public function build(): array
    {
        $workspace = $this->context->get();
        $profile = InvestorProfile::query()->first();

        $accounts = Account::query()->active()->withBalance()->get();
        $liquid = $accounts->filter(fn ($a) => in_array($a->type, [AccountType::Checking, AccountType::Savings, AccountType::Cash], true))->sum('balance');
        $creditCardDebt = $accounts->where('type', AccountType::CreditCard)->sum('balance');

        $flow = $this->reports->monthlyCashflow(6, now()->subMonthNoOverflow());
        $avgIncome = round($flow->avg('income') ?? 0, 2);
        $avgExpense = round($flow->avg('expense') ?? 0, 2);

        $topExpenses = $this->reports->byCategory('expense', now()->subMonthsNoOverflow(3)->startOfMonth()->toDateString(), now()->toDateString())
            ->take(8)->map(fn ($r) => ['categoria' => $r['name'], 'total_3_meses' => $r['total'], 'percentual' => $r['percent']])->all();

        $summary = $this->portfolio->summary($profile);
        $emergencyMonths = $profile?->emergency_fund_months ?? 6;

        return [
            'data_referencia' => now()->toDateString(),
            'tipo_de_espaco' => $workspace?->type->label(),
            'moeda' => $workspace?->currency ?? 'BRL',
            'perfil_investidor' => $profile ? [
                'perfil_de_risco' => $profile->risk_profile->label(),
                'horizonte_anos' => $profile->horizon_years,
                'aporte_mensal_planejado' => (float) $profile->monthly_contribution,
                'meses_de_reserva_desejados' => $emergencyMonths,
                'objetivos' => $profile->objectives,
                'alocacao_alvo_percentual' => $profile->targetAllocation(),
            ] : 'não preenchido',
            'fluxo_de_caixa' => [
                'media_mensal_receitas_6m' => $avgIncome,
                'media_mensal_despesas_6m' => $avgExpense,
                'taxa_de_poupanca_percentual' => $avgIncome > 0 ? round(($avgIncome - $avgExpense) / $avgIncome * 100, 1) : null,
                'historico_mensal' => $flow->map(fn ($m) => ['mes' => $m['month'], 'receitas' => $m['income'], 'despesas' => $m['expense']])->all(),
                'maiores_despesas_por_categoria' => $topExpenses,
            ],
            'patrimonio' => [
                'saldo_liquido_em_contas' => round($liquid, 2),
                'saldo_cartoes_de_credito' => round($creditCardDebt, 2),
                'reserva_de_emergencia_alvo' => round($avgExpense * $emergencyMonths, 2),
                'reserva_cobre_meses' => $avgExpense > 0 ? round($liquid / $avgExpense, 1) : null,
                'total_investido_valor_de_mercado' => $summary['totals']['market_value'],
            ],
            'carteira' => [
                'totais' => $summary['totals'],
                'alocacao_por_classe' => $summary['allocation']->map(fn ($a) => [
                    'classe' => $a['label'],
                    'valor' => $a['value'],
                    'percentual_atual' => $a['percent'],
                    'percentual_alvo' => $a['target'],
                ])->all(),
                'ativos' => $summary['positions']->filter(fn ($p) => $p['quantity'] > 0 || $p['income'] > 0)->map(fn ($p) => [
                    'ativo' => $p['asset']->ticker ?: $p['asset']->name,
                    'classe' => $p['asset']->asset_class->label(),
                    'taxa_ou_indexador' => $p['asset']->rate_description,
                    'vencimento' => $p['asset']->maturity_date?->toDateString(),
                    'quantidade' => $p['quantity'],
                    'preco_medio' => $p['average_cost'],
                    'preco_atual' => $p['current_price'],
                    'valor_de_mercado' => $p['market_value'],
                    'rentabilidade_percentual' => $p['unrealized_percent'],
                    'proventos_recebidos' => $p['income'],
                ])->values()->all(),
            ],
            'metas' => Goal::query()->get()->map(fn (Goal $g) => [
                'meta' => $g->name,
                'valor_alvo' => (float) $g->target_amount,
                'acumulado' => (float) $g->current_amount,
                'prazo' => $g->target_date?->toDateString(),
                'aporte_mensal_necessario' => $g->monthlyNeeded(),
            ])->all(),
        ];
    }
}
