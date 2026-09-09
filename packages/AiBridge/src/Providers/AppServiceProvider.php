<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Contracts\ToolProviderInterface::class, \App\Services\CachedControllerToolProvider::class);
        $this->app->bind(\App\Contracts\ToolSelectorInterface::class, \App\Services\SemanticToolSelector::class);
        $this->app->bind(\App\Contracts\AiProcessorInterface::class, \App\Services\AiService::class);
    }
    public function boot(): void
    {
        //
    }
}