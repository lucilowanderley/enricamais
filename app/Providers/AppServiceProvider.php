<?php

namespace App\Providers;

use App\Services\Ai\AdvisorClient;
use App\Services\Ai\AnthropicAdvisorClient;
use App\Support\WorkspaceContext;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(WorkspaceContext::class);

        $this->app->bind(AdvisorClient::class, fn () => new AnthropicAdvisorClient(
            apiKey: config('services.anthropic.api_key'),
            model: config('services.anthropic.model'),
            effort: config('services.anthropic.effort'),
            maxTokens: config('services.anthropic.max_tokens'),
        ));
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        Paginator::useTailwind();

        Gate::define('write', fn () => app(WorkspaceContext::class)->canWrite());
        Gate::define('manage-workspace', fn () => app(WorkspaceContext::class)->canManage());
    }
}
