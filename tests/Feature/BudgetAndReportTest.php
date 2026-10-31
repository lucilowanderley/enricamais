<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetAndReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_progress_includes_subcategories(): void
    {
        [$user] = $this->userWithWorkspace();
        $food = Category::where('name', 'Alimentação')->whereNull('parent_id')->first();
        $market = Category::where('name', 'Supermercado')->first();
        $account = Account::first();

        $this->actingAs($user)->post('/orcamentos', ['month' => now()->format('Y-m'), 'amounts' => [$food->id => '1.000,00']])->assertRedirect();
        $this->assertEquals(1000, (float) Budget::first()->amount);

        Transaction::create(['account_id' => $account->id, 'category_id' => $market->id, 'type' => 'expense', 'status' => 'paid', 'amount' => 800, 'date' => now(), 'description' => 'Mercado']);
        Transaction::create(['account_id' => $account->id, 'category_id' => $food->id, 'type' => 'expense', 'status' => 'pending', 'amount' => 400, 'date' => now(), 'description' => 'Restaurante']);

        $row = app(ReportService::class)->budgetProgress(now())->first();
        $this->assertEquals(1200, $row['spent']);
        $this->assertEquals(120, $row['percent']);

        // copiar para o próximo mês
        $this->actingAs($user)->post('/orcamentos/copiar', ['month' => now()->addMonthNoOverflow()->format('Y-m')])->assertRedirect();
        $this->assertSame(2, Budget::count());
    }

    public function test_cashflow_and_income_statement(): void
    {
        $this->userWithWorkspace();
        $account = Account::first();
        $salary = Category::where('name', 'Salário')->first();
        $rent = Category::where('name', 'Aluguel / Financiamento')->first();

        Transaction::create(['account_id' => $account->id, 'category_id' => $salary->id, 'type' => 'income', 'status' => 'paid', 'amount' => 5000, 'date' => now()->startOfMonth(), 'description' => 's']);
        Transaction::create(['account_id' => $account->id, 'category_id' => $rent->id, 'type' => 'expense', 'status' => 'paid', 'amount' => 2000, 'date' => now()->startOfMonth(), 'description' => 'a']);
        Transaction::create(['account_id' => $account->id, 'category_id' => $rent->id, 'type' => 'expense', 'status' => 'paid', 'amount' => 1000, 'date' => now()->subMonthNoOverflow()->startOfMonth(), 'description' => 'b']);

        $reports = app(ReportService::class);
        $flow = $reports->monthlyCashflow(2);
        $this->assertEquals(1000, $flow[0]['expense']);
        $this->assertEquals(5000, $flow[1]['income']);
        $this->assertEquals(3000, $flow[1]['net']);

        $dre = $reports->incomeStatement(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());
        $this->assertEquals(3000, $dre['result']);
        $this->assertEquals(60.0, $dre['margin']);
        $this->assertSame('Moradia', $dre['expense']->first()['name']); // subcategoria agrupada na principal
    }
}
