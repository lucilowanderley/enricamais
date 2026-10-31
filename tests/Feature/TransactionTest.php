<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_balances_follow_income_expense_and_transfers(): void
    {
        [$user] = $this->userWithWorkspace();
        $checking = Account::create(['name' => 'Corrente', 'type' => 'checking', 'initial_balance' => 1000]);
        $savings = Account::create(['name' => 'Poupança', 'type' => 'savings', 'initial_balance' => 0]);
        $salary = Category::where('name', 'Salário')->first();
        $market = Category::where('name', 'Supermercado')->first();

        $this->actingAs($user)->post('/lancamentos', [
            'type' => 'income', 'status' => 'paid', 'amount' => '5.000,00', 'date' => now()->toDateString(),
            'description' => 'Salário', 'account_id' => $checking->id, 'category_id' => $salary->id,
        ])->assertRedirect();

        $this->actingAs($user)->post('/lancamentos', [
            'type' => 'expense', 'status' => 'paid', 'amount' => '250,50', 'date' => now()->toDateString(),
            'description' => 'Mercado', 'account_id' => $checking->id, 'category_id' => $market->id,
        ])->assertRedirect();

        $this->actingAs($user)->post('/lancamentos', [
            'type' => 'transfer', 'status' => 'paid', 'amount' => '1000', 'date' => now()->toDateString(),
            'description' => 'Guardar', 'account_id' => $checking->id, 'destination_account_id' => $savings->id,
            'category_id' => $market->id, // deve ser ignorada em transferências
        ])->assertRedirect();

        // pendente não altera o saldo
        $this->actingAs($user)->post('/lancamentos', [
            'type' => 'expense', 'status' => 'pending', 'amount' => '99', 'date' => now()->toDateString(),
            'description' => 'Boleto futuro', 'account_id' => $checking->id,
        ])->assertRedirect();

        $this->assertEquals(4749.50, $checking->currentBalance());
        $this->assertEquals(1000.00, $savings->currentBalance());
        $this->assertNull(Transaction::where('type', 'transfer')->first()->category_id);
    }

    public function test_transfer_requires_a_different_destination(): void
    {
        [$user] = $this->userWithWorkspace();
        $account = Account::first();

        $this->actingAs($user)->post('/lancamentos', [
            'type' => 'transfer', 'status' => 'paid', 'amount' => '10', 'date' => now()->toDateString(),
            'description' => 'x', 'account_id' => $account->id, 'destination_account_id' => $account->id,
        ])->assertSessionHasErrors('destination_account_id');
    }

    public function test_installments_split_the_total_and_keep_future_ones_pending(): void
    {
        [$user] = $this->userWithWorkspace();
        $account = Account::first();

        $this->actingAs($user)->post('/lancamentos', [
            'type' => 'expense', 'status' => 'paid', 'amount' => '1000,00', 'date' => '2026-01-31',
            'description' => 'Notebook', 'account_id' => $account->id, 'installments' => 3,
        ])->assertRedirect();

        $parts = Transaction::orderBy('installment_number')->get();
        $this->assertCount(3, $parts);
        $this->assertEquals(1000.00, round($parts->sum('amount'), 2));
        $this->assertSame('Notebook (1/3)', $parts[0]->description);
        $this->assertSame('2026-02-28', $parts[1]->date->toDateString());
        $this->assertSame(['paid', 'pending', 'pending'], $parts->map(fn ($t) => $t->status->value)->all());

        // excluir a 2ª parcela "e as seguintes"
        $this->actingAs($user)->delete("/lancamentos/{$parts[1]->id}", ['all_installments' => 1])->assertRedirect();
        $this->assertSame(1, Transaction::count());
    }

    public function test_toggle_status_and_csv_export(): void
    {
        [$user] = $this->userWithWorkspace();
        $t = Transaction::create(['account_id' => Account::first()->id, 'type' => 'expense', 'status' => 'pending', 'amount' => 10, 'date' => now(), 'description' => 'Café']);

        $this->actingAs($user)->patch("/lancamentos/{$t->id}/situacao")->assertRedirect();
        $this->assertSame('paid', $t->fresh()->status->value);

        $csv = $this->actingAs($user)->get('/lancamentos/exportar')->assertOk()->streamedContent();
        $this->assertStringContainsString('Café', $csv);
        $this->assertStringContainsString('-10,00', $csv);
    }
}
