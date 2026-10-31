<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AiConversation;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Ai\AdvisorClient;
use App\Services\Ai\AdvisorException;
use App\Services\Ai\AdvisorReply;
use App\Services\Ai\FinancialSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FakeAdvisorClient implements AdvisorClient
{
    public array $calls = [];

    public ?string $fail = null;

    public function complete(array $system, array $messages): AdvisorReply
    {
        if ($this->fail) {
            throw new AdvisorException($this->fail);
        }
        $this->calls[] = compact('system', 'messages');

        return new AdvisorReply('**Resposta** número '.count($this->calls), 'claude-opus-5-5', 100, 50);
    }

    public function isConfigured(): bool
    {
        return true;
    }
}

class AdvisorTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_setup_instructions_when_api_key_is_missing(): void
    {
        [$user] = $this->userWithWorkspace();

        $this->actingAs($user)->get('/investimentos/assistente')->assertOk()->assertSee('ANTHROPIC_API_KEY');
        $this->actingAs($user)->post('/investimentos/assistente', ['question' => 'Onde investir?'])
            ->assertSessionHasErrors('question');
        $this->assertSame(0, AiConversation::count());
    }

    public function test_conversation_flow_sends_history_and_financial_context(): void
    {
        [$user] = $this->userWithWorkspace();
        $fake = new FakeAdvisorClient;
        $this->app->instance(AdvisorClient::class, $fake);

        $this->actingAs($user)->post('/investimentos/assistente', ['quick' => 'reserva'])->assertRedirect();
        $conversation = AiConversation::firstOrFail();
        $this->actingAs($user)->post("/investimentos/assistente/{$conversation->id}", ['question' => 'E se eu ganhar um bônus?'])->assertRedirect();

        $this->assertCount(4, $conversation->messages()->get());
        $this->assertCount(2, $fake->calls);
        $this->assertSame(['user', 'assistant', 'user'], array_column($fake->calls[1]['messages'], 'role'));
        $this->assertStringContainsString('<dados_financeiros_do_usuario>', $fake->calls[0]['system'][1]['text']);
        $this->assertArrayHasKey('cacheControl', $fake->calls[0]['system'][0]);

        $this->actingAs($user)->get("/investimentos/assistente/{$conversation->id}")
            ->assertOk()->assertSee('<strong>Resposta</strong>', false);
    }

    public function test_failed_call_does_not_leave_orphan_question(): void
    {
        [$user] = $this->userWithWorkspace();
        $fake = new FakeAdvisorClient;
        $this->app->instance(AdvisorClient::class, $fake);

        $this->actingAs($user)->post('/investimentos/assistente', ['question' => 'Primeira pergunta'])->assertRedirect();
        $conversation = AiConversation::firstOrFail();

        $fake->fail = 'Limite de uso atingido';
        $this->actingAs($user)->post("/investimentos/assistente/{$conversation->id}", ['question' => 'Segunda'])
            ->assertSessionHasErrors(['question' => 'Limite de uso atingido']);
        $this->assertCount(2, $conversation->messages()->get());
    }

    public function test_conversations_are_private_to_their_author(): void
    {
        [$user, $workspace] = $this->userWithWorkspace();
        $conversation = AiConversation::create(['user_id' => $user->id, 'title' => 'Minha']);

        $other = User::factory()->create();
        $workspace->members()->attach($other->id, ['role' => 'member']);
        $other->forceFill(['current_workspace_id' => $workspace->id])->save();

        $this->actingAs($other)->get("/investimentos/assistente/{$conversation->id}")->assertNotFound();
    }

    public function test_snapshot_does_not_include_transaction_descriptions(): void
    {
        $this->userWithWorkspace();
        Transaction::create(['account_id' => Account::first()->id, 'type' => 'expense', 'status' => 'paid', 'amount' => 10, 'date' => now()->subMonth(), 'description' => 'Consulta Dr. Fulano CPF 123']);

        $json = json_encode(app(FinancialSnapshot::class)->build(), JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('Fulano', $json);
        $this->assertStringContainsString('fluxo_de_caixa', $json);
    }
}
