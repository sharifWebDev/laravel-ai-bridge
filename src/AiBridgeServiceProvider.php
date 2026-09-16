<?php

namespace Sharifuddin\LaravelAiBridge;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Sharifuddin\LaravelAiBridge\Console\Commands\IndexToolsCommand;
use Sharifuddin\LaravelAiBridge\Console\Commands\ListToolsCommand;
use Sharifuddin\LaravelAiBridge\Console\Commands\ReindexToolsCommand;
use Sharifuddin\LaravelAiBridge\Contracts\AiProcessorInterface;
use Sharifuddin\LaravelAiBridge\Contracts\AiProviderInterface;
use Sharifuddin\LaravelAiBridge\Contracts\EmbeddingProviderInterface;
use Sharifuddin\LaravelAiBridge\Contracts\TenantContextResolverInterface;
use Sharifuddin\LaravelAiBridge\Contracts\ToolProviderInterface;
use Sharifuddin\LaravelAiBridge\Contracts\ToolRegistryInterface;
use Sharifuddin\LaravelAiBridge\Contracts\ToolSelectorInterface;
use Sharifuddin\LaravelAiBridge\Contracts\VectorStoreInterface;
use Sharifuddin\LaravelAiBridge\Embeddings\EmbeddingManager;
use Sharifuddin\LaravelAiBridge\Execution\ArgumentValidator;
use Sharifuddin\LaravelAiBridge\Execution\ToolExecutor;
use Sharifuddin\LaravelAiBridge\Indexing\ToolIndexer;
use Sharifuddin\LaravelAiBridge\Providers\AiProviderManager;
use Sharifuddin\LaravelAiBridge\Registry\ToolRegistry;
use Sharifuddin\LaravelAiBridge\Retrieval\LexicalScorer;
use Sharifuddin\LaravelAiBridge\Retrieval\QueryNormalizer;
use Sharifuddin\LaravelAiBridge\Retrieval\ToolRetriever;
use Sharifuddin\LaravelAiBridge\Security\NullTenantContextResolver;
use Sharifuddin\LaravelAiBridge\Security\PermissionFilter;
use Sharifuddin\LaravelAiBridge\Services\AiService;
use Sharifuddin\LaravelAiBridge\Services\CachedControllerToolProvider;
use Sharifuddin\LaravelAiBridge\Services\SemanticToolSelector;
use Sharifuddin\LaravelAiBridge\VectorStore\VectorStoreManager;

class AiBridgeServiceProvider extends ServiceProvider
{
    /**
     * Register any package services and bindings.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ai-bridge.php', 'ai-bridge');

        // --- Backward-compatible legacy bindings (unchanged behavior) ---
        $this->app->bind(ToolProviderInterface::class, CachedControllerToolProvider::class);
        $this->app->bind(ToolSelectorInterface::class, SemanticToolSelector::class);

        // --- New architecture bindings ---
        $this->app->singleton(ToolRegistryInterface::class, function ($app) {
            $registry = new ToolRegistry();

            foreach (config('ai-bridge.tools', []) as $toolClass) {
                $registry->register($toolClass);
            }

            $hasExplicitControllerConfig = !empty(config('ai-bridge.controllers', []));
            if ($hasExplicitControllerConfig || config('ai-bridge.legacy_controllers.enabled', true)) {
                $registry->mergeLegacyProvider($app->make(ToolProviderInterface::class));
            }

            return $registry;
        });

        $this->app->singleton(EmbeddingProviderInterface::class, fn () => EmbeddingManager::resolve());
        $this->app->singleton(VectorStoreInterface::class, fn () => VectorStoreManager::resolve());
        $this->app->singleton(AiProviderInterface::class, fn () => AiProviderManager::resolveWithFailover());

        $this->app->bindIf(TenantContextResolverInterface::class, NullTenantContextResolver::class);

        $this->app->singleton(PermissionFilter::class);
        $this->app->singleton(QueryNormalizer::class);
        $this->app->singleton(LexicalScorer::class);
        $this->app->singleton(ArgumentValidator::class);

        $this->app->singleton(ToolRetriever::class);
        $this->app->singleton(ToolExecutor::class);
        $this->app->singleton(ToolIndexer::class);

        $this->app->bind(AiProcessorInterface::class, AiService::class);
    }

    /**
     * Bootstrap package services, routes, views, and publishable assets.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ai-bridge');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/ai-bridge.php' => config_path('ai-bridge.php'),
            ], 'ai-bridge-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/ai-bridge'),
            ], 'ai-bridge-views');

            $this->commands([
                IndexToolsCommand::class,
                ReindexToolsCommand::class,
                ListToolsCommand::class,
            ]);
        }

        $this->registerRoutes();
    }

    /**
     * Register package routes with configurable prefixes and middleware.
     */
    protected function registerRoutes(): void
    {
        if (!config('ai-bridge.routes.enabled', true)) {
            return;
        }

        Route::prefix(config('ai-bridge.routes.api_prefix', 'api/ai'))
            ->middleware(config('ai-bridge.routes.api_middleware', ['api']))
            ->group(__DIR__ . '/../routes/api.php');

        Route::prefix(config('ai-bridge.routes.web_prefix', 'ai'))
            ->middleware(config('ai-bridge.routes.web_middleware', ['web']))
            ->group(__DIR__ . '/../routes/web.php');
    }
}
