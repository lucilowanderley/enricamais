<?php

namespace App\Services\Ai;

use App\Models\AiConversation;
use App\Models\AiMessage;
use Illuminate\Support\Str;

/**
 * Assistente de investimentos: combina o retrato financeiro do usuário com o
 * histórico da conversa e pede orientações ao Claude.
 */
class InvestmentAdvisor
{
    public const QUICK_PROMPTS = [
        'diagnostico' => 'Faça um diagnóstico completo da minha saúde financeira e da minha carteira de investimentos, com pontos fortes, riscos e as 5 ações prioritárias para os próximos 90 dias.',
        'rebalanceamento' => 'Compare minha alocação atual com a alocação-alvo do meu perfil e sugira como distribuir os próximos aportes para rebalancear a carteira, sem precisar vender ativos se possível.',
        'reserva' => 'Avalie minha reserva de emergência: quanto devo ter, quanto falta, onde é adequado mantê-la e em quanto tempo consigo completá-la com minha capacidade de poupança.',
        'renda_fixa' => 'Analise meus investimentos de renda fixa (indexadores, prazos, vencimentos e liquidez) e indique oportunidades de melhoria considerando o cenário atual de juros.',
        'aposentadoria' => 'Com base no meu fluxo de caixa e na carteira atual, simule um plano de independência financeira: quanto preciso acumular, quanto aportar por mês e qual estratégia seguir.',
        'despesas' => 'Analise minhas despesas por categoria e indique onde posso economizar para aumentar meus aportes mensais, com metas concretas por categoria.',
    ];

    public function __construct(
        private readonly AdvisorClient $client,
        private readonly FinancialSnapshot $snapshot,
    ) {}

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    public function startConversation(int $userId, string $question): AiConversation
    {
        $conversation = AiConversation::create([
            'user_id' => $userId,
            'title' => Str::limit(trim(preg_replace('/\s+/', ' ', $question)), 70),
        ]);

        try {
            $this->ask($conversation, $question);
        } catch (AdvisorException $e) {
            $conversation->delete();
            throw $e;
        }

        return $conversation;
    }

    public function ask(AiConversation $conversation, string $question): AiMessage
    {
        $conversation->messages()->create(['role' => 'user', 'content' => $question]);

        $history = $conversation->messages()->get()
            ->map(fn (AiMessage $m) => ['role' => $m->role, 'content' => $m->content])
            ->values()->all();

        try {
            $reply = $this->client->complete($this->systemPrompt(), $history);
        } catch (AdvisorException $e) {
            // remove a pergunta para que o usuário possa tentar novamente sem duplicar
            $conversation->messages()->latest('id')->first()?->delete();
            throw $e;
        }

        $conversation->touch();

        return $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $reply->text,
            'model' => $reply->model,
            'input_tokens' => $reply->inputTokens,
            'output_tokens' => $reply->outputTokens,
        ]);
    }

    /** @return list<array{type: string, text: string, cacheControl?: array<string, string>}> */
    public function systemPrompt(): array
    {
        $data = json_encode($this->snapshot->build(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return [
            // Bloco fixo: pode ser reaproveitado pelo cache de prompt entre requisições.
            ['type' => 'text', 'text' => self::INSTRUCTIONS, 'cacheControl' => ['type' => 'ephemeral']],
            // Bloco variável: retrato financeiro atualizado a cada pergunta.
            ['type' => 'text', 'text' => "<dados_financeiros_do_usuario>\n{$data}\n</dados_financeiros_do_usuario>"],
        ];
    }

    private const INSTRUCTIONS = <<<'TXT'
Você é o assistente de investimentos do ENRICA+, um sistema brasileiro de gestão financeira pessoal, familiar e empresarial. Você conversa em português do Brasil com pessoas que querem organizar suas finanças e investir melhor.

Como trabalhar:
- Baseie cada resposta nos dados financeiros do usuário fornecidos no bloco <dados_financeiros_do_usuario>. Cite os números relevantes (saldos, médias, percentuais da carteira) para que a orientação seja concreta. Se faltar um dado importante (por exemplo, perfil de investidor não preenchido ou preços desatualizados), diga qual é e como ele mudaria a recomendação.
- Siga uma ordem de prioridades saudável: controle do orçamento e quitação de dívidas caras (como rotativo de cartão), depois reserva de emergência em aplicações líquidas e de baixo risco, depois investimentos alinhados ao perfil de risco, horizonte e objetivos.
- Considere o contexto brasileiro: Tesouro Direto, CDB, LCI/LCA, fundos, ações, FIIs, ETFs, previdência (PGBL/VGBL), garantia do FGC, tributação (tabela regressiva de IR, isenções de LCI/LCA e de dividendos conforme a legislação vigente, come-cotas) e liquidez. Quando citar regras tributárias ou taxas de mercado, deixe claro que podem mudar e que o usuário deve confirmar os valores atuais.
- Seja específico e prático: proponha valores, percentuais e próximos passos. Para rebalanceamento, prefira direcionar novos aportes antes de sugerir vendas. Explique riscos, custos e liquidez de cada sugestão.
- Não recomende ativos individuais como certeza de ganho nem faça previsões de preço. Ao mencionar exemplos de ativos, apresente-os como exemplos de classe, não como ordem de compra.
- Para espaços empresariais, separe o caixa operacional e o capital de giro do que pode ser investido, e considere as obrigações fiscais da empresa.
- Formate em Markdown com títulos curtos, listas e tabelas quando ajudarem. Seja direto; evite repetir os dados de volta sem análise.
- Encerre orientações de investimento com uma linha curta lembrando que se trata de orientação educacional gerada por IA, que não substitui um profissional certificado (CVM/ANBIMA) e que a decisão final é do usuário.
TXT;
}
