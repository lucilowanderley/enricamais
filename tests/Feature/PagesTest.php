<?php

namespace Tests\Feature;

use App\Models\AiConversation;
use App\Models\Goal;
use App\Models\InvestmentAsset;
use App\Models\User;
use App\Support\WorkspaceContext;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_page_renders_with_demo_data_in_both_workspaces(): void
    {
        $this->seed(DemoSeeder::class);
        $user = User::where('email', 'demo@enrica.app')->firstOrFail();

        foreach ($user->workspaces as $workspace) {
            $user->forceFill(['current_workspace_id' => $workspace->id])->save();
            app(WorkspaceContext::class)->set($workspace);

            $account = $workspace->accounts()->first();
            $transaction = $workspace->transactions()->first();
            $category = $workspace->categories()->first();
            $asset = InvestmentAsset::query()->first();
            $goal = Goal::query()->first();

            $urls = [
                '/', '/lancamentos', '/lancamentos/create', '/lancamentos/create?type=transfer',
                "/lancamentos/{$transaction->id}/edit", '/lancamentos/exportar',
                '/contas', '/contas/create', "/contas/{$account->id}", "/contas/{$account->id}/edit",
                '/categorias', '/categorias/create', "/categorias/{$category->id}/edit",
                '/orcamentos', '/orcamentos?month=2020-01', '/lancamentos?month=lixo', '/metas', '/metas/create', '/relatorios', '/relatorios?months=24',
                '/espacos', '/espacos/novo', '/espaco/configuracoes',
                '/investimentos', '/investimentos/perfil', '/investimentos/ativos/novo', '/investimentos/assistente',
            ];
            if ($asset) {
                $urls[] = "/investimentos/ativos/{$asset->id}";
                $urls[] = "/investimentos/ativos/{$asset->id}/editar";
            }
            if ($goal) {
                $urls[] = "/metas/{$goal->id}/edit";
            }

            foreach ($urls as $url) {
                $this->actingAs($user)->get($url)->assertOk();
            }
        }

        $conversation = AiConversation::create(['user_id' => $user->id, 'title' => 'Teste']);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Oi']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => "## Título\n\n| a | b |\n|---|---|\n| 1 | 2 |\n\n<script>alert(1)</script>"]);

        $this->actingAs($user)->get("/investimentos/assistente/{$conversation->id}")
            ->assertOk()
            ->assertSee('<table>', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
