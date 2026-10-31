<?php

namespace Tests\Feature;

use App\Models\InvestmentAsset;
use App\Models\InvestorProfile;
use App\Services\PortfolioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_position_uses_weighted_average_cost(): void
    {
        [$user] = $this->userWithWorkspace();

        $this->actingAs($user)->post('/investimentos/ativos', [
            'name' => 'Itaú PN', 'ticker' => 'itub4', 'asset_class' => 'stocks', 'currency' => 'BRL', 'current_price' => '40,00',
        ])->assertRedirect();
        $asset = InvestmentAsset::firstOrFail();
        $this->assertSame('ITUB4', $asset->ticker);

        $post = fn (array $d) => $this->actingAs($user)->post("/investimentos/ativos/{$asset->id}/operacoes", $d + ['date' => now()->toDateString()]);

        $post(['type' => 'buy', 'quantity' => '100', 'unit_price' => '30,00', 'fees' => '10,00'])->assertSessionHasNoErrors();
        $post(['type' => 'buy', 'quantity' => '100', 'unit_price' => '20,00'])->assertSessionHasNoErrors();
        $post(['type' => 'sell', 'quantity' => '50', 'unit_price' => '35,00'])->assertSessionHasNoErrors();
        $post(['type' => 'income', 'amount' => '45,50'])->assertSessionHasNoErrors();
        $post(['type' => 'sell', 'quantity' => '1000', 'unit_price' => '35'])->assertSessionHasErrors('quantity');

        $p = app(PortfolioService::class)->position($asset->fresh());

        // custo: 3010 + 2000 = 5010 por 200 ações => médio 25,05
        $this->assertEquals(150, $p['quantity']);
        $this->assertEquals(25.05, $p['average_cost']);
        $this->assertEquals(3757.50, $p['invested']);
        $this->assertEquals(6000.00, $p['market_value']);
        $this->assertEquals(2242.50, $p['unrealized']);
        $this->assertEquals(497.50, $p['realized']); // 1750 − 50 × 25,05
        $this->assertEquals(45.50, $p['income']);
    }

    public function test_profile_allocation_must_sum_100_and_drives_rebalancing(): void
    {
        [$user] = $this->userWithWorkspace();

        $this->actingAs($user)->put('/investimentos/perfil', [
            'risk_profile' => 'moderate', 'horizon_years' => 10, 'emergency_fund_months' => 6,
            'target_allocation' => ['stocks' => 50, 'fixed_income' => 30],
        ])->assertSessionHasErrors('target_allocation');

        $this->actingAs($user)->put('/investimentos/perfil', [
            'risk_profile' => 'moderate', 'horizon_years' => 10, 'emergency_fund_months' => 6, 'monthly_contribution' => '1.500,00',
            'target_allocation' => ['stocks' => 50, 'fixed_income' => 50],
        ])->assertRedirect(route('investments.index'));

        $profile = InvestorProfile::firstOrFail();
        $this->assertEquals(1500, (float) $profile->monthly_contribution);

        $cdb = InvestmentAsset::create(['name' => 'CDB', 'asset_class' => 'fixed_income', 'current_price' => 1]);
        $cdb->operations()->create(['type' => 'buy', 'date' => now(), 'quantity' => 1000, 'unit_price' => 1, 'amount' => 1000]);

        $allocation = app(PortfolioService::class)->summary($profile)['allocation']->keyBy(fn ($r) => $r['class']->value);
        $this->assertEquals(100, $allocation['fixed_income']['percent']);
        $this->assertEquals(500, $allocation['stocks']['gap_value']); // aportar R$ 500 em ações
        $this->assertEquals(-500, $allocation['fixed_income']['gap_value']);

        $this->actingAs($user)->get('/investimentos')->assertOk()->assertSee('Aportar R$ 500,00');
    }
}
