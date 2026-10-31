<?php

namespace App\Services\Ai;

final class AdvisorReply
{
    public function __construct(
        public readonly string $text,
        public readonly string $model,
        public readonly ?int $inputTokens = null,
        public readonly ?int $outputTokens = null,
        public readonly bool $refused = false,
    ) {}
}
