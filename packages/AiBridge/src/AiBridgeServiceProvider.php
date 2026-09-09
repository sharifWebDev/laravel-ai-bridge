<?php

namespace Sharifuddin\LaravelAiBridge;

use Illuminate\Support\ServiceProvider;
use Sharifuddin\LaravelAiBridge\Contracts\AiProcessorInterface;
use Sharifuddin\LaravelAiBridge\Contracts\ToolProviderInterface;
use Sharifuddin\LaravelAiBridge\Contracts\ToolSelectorInterface;
use Sharifuddin\LaravelAiBridge\Services\AiService;
use Sharifuddin\LaravelAiBridge\Services\CachedControllerToolProvider;
use Sharifuddin\LaravelAiBridge\Services\SemanticToolSelector;

class AiBridgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ToolProviderInterface::class, CachedControllerToolProvider::class);
        $this->app->bind(ToolSelectorInterface::class, SemanticToolSelector::class);
        $this->app->bind(AiProcessorInterface::class, AiService::class);
    }

    public function boot(): void
    {
        // API Routes লোড করা (স্বয়ংক্রিয়ভাবে /api প্রিফিক্স পাবে)
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Web Routes লোড করা (মেইন প্রজেক্টের web মিডলওয়্যার পাবে)
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        
        // প্যাকেজ ভিউ লোড করা (namespace: 'ai-bridge')
    	$this->loadViewsFrom(__DIR__ . '/../resources/views', 'ai-bridge');
    }
}
