<?php

use App\Support\Money;
use App\Support\WorkspaceContext;

if (! function_exists('money')) {
    function money(float|int|string|null $value, ?string $currency = null): string
    {
        return Money::format($value, $currency ?? (app(WorkspaceContext::class)->get()?->currency ?? 'BRL'));
    }
}

if (! function_exists('workspace')) {
    function workspace(): WorkspaceContext
    {
        return app(WorkspaceContext::class);
    }
}
