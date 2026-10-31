<?php

namespace App\Services\Ai;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\RateLimitException;

/**
 * Integração com a API do Claude (Anthropic) via SDK oficial para PHP.
 */
class AnthropicAdvisorClient implements AdvisorClient
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $model,
        private readonly string $effort,
        private readonly int $maxTokens,
    ) {}

    public function isConfigured(): bool
    {
        return filled($this->apiKey);
    }

    public function complete(array $system, array $messages): AdvisorReply
    {
        if (! $this->isConfigured()) {
            throw new AdvisorException('A chave da API do Claude (ANTHROPIC_API_KEY) não está configurada.');
        }

        $client = new Client(apiKey: $this->apiKey);

        try {
            // "fallbacks: default" faz a própria API repetir o pedido em um modelo
            // alternativo caso o modelo principal recuse por política de segurança.
            $message = $client->beta->messages->create(
                model: $this->model,
                maxTokens: $this->maxTokens,
                system: $system,
                messages: $messages,
                thinking: ['type' => 'adaptive'],
                outputConfig: ['effort' => $this->effort],
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
            );
        } catch (AuthenticationException) {
            throw new AdvisorException('A chave da API do Claude é inválida. Verifique ANTHROPIC_API_KEY no arquivo .env.');
        } catch (RateLimitException) {
            throw new AdvisorException('Limite de uso da API do Claude atingido. Tente novamente em instantes.');
        } catch (APIStatusException $e) {
            throw new AdvisorException('A API do Claude retornou um erro: '.$e->getMessage(), previous: $e);
        } catch (APIConnectionException $e) {
            throw new AdvisorException('Não foi possível conectar à API do Claude. Verifique sua conexão.', previous: $e);
        }

        if ($message->stopReason === 'refusal') {
            return new AdvisorReply(
                text: 'Não consigo ajudar com esse pedido. Reformule a pergunta focando no seu planejamento financeiro e de investimentos.',
                model: (string) $message->model,
                refused: true,
            );
        }

        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        return new AdvisorReply(
            text: trim($text),
            model: (string) $message->model,
            inputTokens: $message->usage->inputTokens ?? null,
            outputTokens: $message->usage->outputTokens ?? null,
        );
    }
}
