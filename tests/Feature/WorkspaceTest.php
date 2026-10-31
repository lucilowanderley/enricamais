<?php

namespace Tests\Feature;

use App\Enums\WorkspaceType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_from_another_workspace_is_not_accessible(): void
    {
        [$alice, $aliceWs] = $this->userWithWorkspace();
        $aliceAccount = Account::first();
        Transaction::create(['account_id' => $aliceAccount->id, 'type' => 'expense', 'status' => 'paid', 'amount' => 10, 'date' => now(), 'description' => 'Segredo da Alice']);

        [$bob] = $this->userWithWorkspace();

        $this->actingAs($bob)->get("/contas/{$aliceAccount->id}")->assertNotFound();
        $this->actingAs($bob)->put("/contas/{$aliceAccount->id}", ['name' => 'hack', 'type' => 'checking', 'color' => '#000000'])->assertNotFound();
        $this->actingAs($bob)->get('/lancamentos')->assertOk()->assertDontSee('Segredo da Alice');

        // Bob também não consegue lançar em conta da Alice
        $this->actingAs($bob)->post('/lancamentos', [
            'type' => 'expense', 'status' => 'paid', 'amount' => '5', 'date' => now()->toDateString(),
            'description' => 'x', 'account_id' => $aliceAccount->id,
        ])->assertSessionHasErrors('account_id');

        // nem ativar o espaço dela
        $this->actingAs($bob)->post("/espacos/{$aliceWs->id}/ativar")->assertNotFound();
    }

    public function test_user_can_create_and_switch_between_workspaces(): void
    {
        [$user, $personal] = $this->userWithWorkspace();

        $this->actingAs($user)->post('/espacos', ['name' => 'Minha Empresa', 'type' => 'business', 'currency' => 'BRL'])->assertRedirect();
        $company = $user->workspaces()->where('name', 'Minha Empresa')->firstOrFail();
        $this->assertSame($company->id, $user->fresh()->current_workspace_id);
        $this->assertTrue($company->categories()->withoutGlobalScopes()->where('name', 'Pró-labore')->exists());

        $this->actingAs($user)->post("/espacos/{$personal->id}/ativar")->assertRedirect();
        $this->assertSame($personal->id, $user->fresh()->current_workspace_id);
    }

    public function test_family_members_share_data_and_viewers_are_read_only(): void
    {
        [$owner, $family] = $this->userWithWorkspace(WorkspaceType::Family);
        $partner = User::factory()->create();
        $accountant = User::factory()->create();

        $this->actingAs($owner)->post('/espaco/membros', ['email' => $partner->email, 'role' => 'member'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post('/espaco/membros', ['email' => $accountant->email, 'role' => 'viewer'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post('/espaco/membros', ['email' => 'ninguem@example.com', 'role' => 'member'])->assertSessionHasErrors('email');

        $partner->forceFill(['current_workspace_id' => $family->id])->save();
        $accountant->forceFill(['current_workspace_id' => $family->id])->save();
        $account = Account::first();

        $payload = ['type' => 'expense', 'status' => 'paid', 'amount' => '20', 'date' => now()->toDateString(), 'description' => 'Padaria', 'account_id' => $account->id];

        $this->actingAs($partner)->post('/lancamentos', $payload)->assertRedirect();
        $this->actingAs($accountant)->get('/lancamentos')->assertOk()->assertSee('Padaria');
        $this->actingAs($accountant)->post('/lancamentos', $payload)->assertForbidden();

        // membro comum não gerencia membros
        $this->actingAs($partner)->post('/espaco/membros', ['email' => $accountant->email, 'role' => 'admin'])->assertForbidden();

        // proprietário não pode ser removido
        $this->actingAs($owner)->delete("/espaco/membros/{$owner->id}")->assertStatus(422);
    }
}
