<?php

namespace App\Services\Ai;

interface AdvisorClient
{
    /**
     * @param  list<array{type: string, text: string, cacheControl?: array<string, string>}>  $system
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function complete(array $system, array $messages): AdvisorReply;

    public function isConfigured(): bool;
}
