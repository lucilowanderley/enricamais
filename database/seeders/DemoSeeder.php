<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\RiskProfile;
use App\Enums\WorkspaceRole;
use App\Enums\WorkspaceType;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Goal;
use App\Models\InvestmentAsset;
use App\Models\InvestorProfile;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceService;
use App\Support\WorkspaceContext;
use Illuminate\Database\Seeder;

/**
 * Dados de demonstração: usuário demo@enrica.app / senha "password".
 */
class DemoSeeder extends Seeder
{
    public function __construct(private readonly WorkspaceService $workspaces, private readonly WorkspaceContext $context) {}

    public function run(): void
    {
        $user = User::firstOrCreate(['email' => 'demo@enrica.app'], ['name' => 'Usuário Demo', 'password' => 'password']);
        $partner = User::firstOrCreate(['email' => 'parceira@enrica.app'], ['name' => 'Parceira Demo', 'password' => 'password']);

        if ($user->workspaces()->exists()) {
            return; // já semeado
        }

        $family = $this->workspaces->create($user, 'Família Demo', WorkspaceType::Family);
        $family->members()->attach($partner->id, ['role' => WorkspaceRole::Member->value]);
        $this->seedHousehold($family, $user);

        $company = $this->workspaces->create($user, 'Demo Serviços Ltda', WorkspaceType::Business, 'BRL', '00.000.000/0001-00');
        $this->seedBusiness($company, $user);

        $user->forceFill(['current_workspace_id' => $family->id])->save();
        $this->context->set(null);
    }

    private function seedHousehold(Workspace $ws, User $user): void
    {
        $this->context->set($ws, WorkspaceRole::Owner);
        $cat = fn (string $name) => Category::where('name', $name)->value('id');

        $checking = Account::create(['name' => 'Conta corrente', 'type' => AccountType::Checking, 'institution' => 'Banco Exemplo', 'initial_balance' => 4200, 'color' => '#2563eb']);
        $savings = Account::create(['name' => 'Poupança', 'type' => AccountType::Savings, 'institution' => 'Banco Exemplo', 'initial_balance' => 12000, 'color' => '#0d9488']);
        $card = Account::create(['name' => 'Cartão de crédito', 'type' => AccountType::CreditCard, 'institution' => 'Banco Exemplo', 'initial_balance' => 0, 'credit_limit' => 8000, 'color' => '#7c3aed']);

        for ($i = 6; $i >= 0; $i--) {
            $m = now()->subMonthsNoOverflow($i)->startOfMonth();
            $status = fn (int $day) => $m->copy()->day($day)->isFuture() ? 'pending' : 'paid';

            $rows = [
                ['income', $checking, 'Salário', 'Salário', 9800, 5],
                ['income', $checking, 'Freelance', 'Projeto freelance', rand(0, 1) ? 1500 : 0, 18],
                ['expense', $checking, 'Aluguel / Financiamento', 'Aluguel', 2600, 10],
                ['expense', $checking, 'Condomínio', 'Condomínio', 650, 10],
                ['expense', $checking, 'Energia', 'Conta de luz', rand(220, 340), 15],
                ['expense', $checking, 'Internet / Telefone', 'Internet + celular', 210, 12],
                ['expense', $card, 'Supermercado', 'Supermercado', rand(1300, 1700), 8],
                ['expense', $card, 'Restaurantes', 'Restaurantes', rand(350, 650), 20],
                ['expense', $card, 'Combustível', 'Posto de combustível', rand(380, 520), 14],
                ['expense', $checking, 'Plano de saúde', 'Plano de saúde', 890, 7],
                ['expense', $checking, 'Escola / Faculdade', 'Mensalidade escolar', 1200, 6],
                ['expense', $card, 'Assinaturas', 'Streaming e apps', 119.9, 3],
                ['expense', $card, 'Farmácia', 'Farmácia', rand(80, 240), 22],
            ];
            foreach ($rows as [$type, $account, $category, $desc, $amount, $day]) {
                if ($amount <= 0) {
                    continue;
                }
                Transaction::create([
                    'account_id' => $account->id, 'category_id' => $cat($category), 'user_id' => $user->id,
                    'type' => $type, 'status' => $status($day), 'amount' => $amount,
                    'date' => $m->copy()->day($day)->toDateString(), 'description' => $desc,
                ]);
            }

            // Pagamento da fatura do cartão e aplicação mensal
            Transaction::create(['account_id' => $checking->id, 'destination_account_id' => $card->id, 'type' => 'transfer', 'status' => $status(25), 'amount' => 2600, 'date' => $m->copy()->day(25)->toDateString(), 'description' => 'Pagamento da fatura', 'user_id' => $user->id]);
            Transaction::create(['account_id' => $checking->id, 'destination_account_id' => $savings->id, 'type' => 'transfer', 'status' => $status(6), 'amount' => 800, 'date' => $m->copy()->day(6)->toDateString(), 'description' => 'Reserva mensal', 'user_id' => $user->id]);
        }

        foreach (['Moradia' => 3800, 'Alimentação' => 2300, 'Transporte' => 600, 'Saúde' => 1100, 'Lazer' => 400, 'Educação' => 1300] as $name => $amount) {
            Budget::create(['category_id' => $cat($name), 'month' => now()->startOfMonth()->toDateString(), 'amount' => $amount]);
        }

        Goal::create(['name' => 'Reserva de emergência', 'target_amount' => 60000, 'current_amount' => 17600, 'target_date' => now()->addMonths(24)->toDateString(), 'color' => '#0d9488']);
        Goal::create(['name' => 'Viagem em família', 'target_amount' => 15000, 'current_amount' => 4200, 'target_date' => now()->addMonths(10)->toDateString(), 'color' => '#f59e0b']);

        InvestorProfile::create([
            'risk_profile' => RiskProfile::Moderate, 'horizon_years' => 15, 'monthly_contribution' => 1500,
            'emergency_fund_months' => 6, 'objectives' => 'Completar a reserva de emergência, pagar a faculdade dos filhos e aposentar aos 60 anos com renda mensal de R$ 12 mil.',
        ]);

        $assets = [
            ['CDB Banco Exemplo 2027', null, 'fixed_income', '112% CDI', now()->addYears(2), [['buy', 15, 1, 10000]], 11180],
            ['Tesouro IPCA+ 2035', null, 'treasury', 'IPCA + 6,2%', now()->addYears(10), [['buy', 9, 2.5, 2000]], 2150],
            ['Itaú Unibanco PN', 'ITUB4', 'stocks', null, null, [['buy', 8, 200, 31.5], ['buy', 4, 100, 34.2]], 36.8],
            ['WEG ON', 'WEGE3', 'stocks', null, null, [['buy', 7, 80, 41.0]], 38.9],
            ['CSHG Logística', 'HGLG11', 'reits', null, null, [['buy', 6, 30, 158.0]], 162.4],
            ['iShares S&P 500', 'IVVB11', 'etfs', null, null, [['buy', 5, 40, 290.0]], 335.1],
        ];
        foreach ($assets as [$name, $ticker, $class, $rate, $maturity, $ops, $price]) {
            $asset = InvestmentAsset::create(['name' => $name, 'ticker' => $ticker, 'asset_class' => $class, 'rate_description' => $rate, 'maturity_date' => $maturity, 'institution' => 'Corretora Exemplo', 'current_price' => $price, 'price_updated_at' => now()]);
            foreach ($ops as [$type, $monthsAgo, $qty, $unit]) {
                $asset->operations()->create(['type' => $type, 'date' => now()->subMonths($monthsAgo)->toDateString(), 'quantity' => $qty, 'unit_price' => $unit, 'amount' => round($qty * $unit, 2), 'fees' => 0]);
            }
        }
        $hglg = InvestmentAsset::where('ticker', 'HGLG11')->first();
        foreach ([3, 2, 1] as $monthsAgo) {
            $hglg->operations()->create(['type' => 'income', 'date' => now()->subMonths($monthsAgo)->toDateString(), 'quantity' => 0, 'unit_price' => 0, 'amount' => 33.0, 'fees' => 0]);
        }
    }

    private function seedBusiness(Workspace $ws, User $user): void
    {
        $this->context->set($ws, WorkspaceRole::Owner);
        $cat = fn (string $name) => Category::where('name', $name)->value('id');

        $bank = Account::where('name', 'Conta empresa')->first();
        $bank->update(['initial_balance' => 35000, 'institution' => 'Banco PJ Exemplo']);

        for ($i = 6; $i >= 0; $i--) {
            $m = now()->subMonthsNoOverflow($i)->startOfMonth();
            $status = fn (int $day) => $m->copy()->day($day)->isFuture() ? 'pending' : 'paid';
            $rows = [
                ['income', 'Serviços', 'Contrato cliente A', 18000, 5, 'Cliente A'],
                ['income', 'Serviços', 'Projeto cliente B', rand(6000, 14000), 15, 'Cliente B'],
                ['expense', 'Salários', 'Folha de pagamento', 9500, 5, null],
                ['expense', 'Pró-labore', 'Pró-labore sócios', 6000, 5, null],
                ['expense', 'Encargos', 'INSS / FGTS', 2100, 20, null],
                ['expense', 'Aluguel', 'Aluguel escritório', 3200, 10, 'Imobiliária'],
                ['expense', 'Software / SaaS', 'Ferramentas SaaS', rand(700, 1100), 12, null],
                ['expense', 'Simples Nacional / DAS', 'DAS Simples Nacional', rand(1800, 2600), 20, 'Receita Federal'],
                ['expense', 'Contabilidade', 'Honorários contábeis', 900, 10, 'Escritório contábil'],
                ['expense', 'Marketing', 'Anúncios online', rand(500, 1500), 18, null],
            ];
            foreach ($rows as [$type, $category, $desc, $amount, $day, $party]) {
                Transaction::create([
                    'account_id' => $bank->id, 'category_id' => $cat($category), 'user_id' => $user->id,
                    'type' => $type, 'status' => $status($day), 'amount' => $amount,
                    'date' => $m->copy()->day($day)->toDateString(), 'description' => $desc, 'counterparty' => $party,
                ]);
            }
        }
    }
}
