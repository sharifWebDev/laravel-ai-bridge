<?php

namespace Sharifuddin\LaravelAiBridge\Providers;

use Sharifuddin\LaravelAiBridge\Contracts\AiProviderInterface;

/**
 * Resolves the configured chat/generation provider (ai-bridge.provider).
 * Gemini ships fully implemented; additional providers (OpenAI, Anthropic)
 * only need to implement AiProviderInterface and be added here.
 */
final class AiProviderManager
{
    public static function resolve(): AiProviderInterface
    {
        $driver = (string) config('ai-bridge.provider', 'gemini');

        return match ($driver) {
            'gemini' => new GeminiAiProvider(),
            default => throw new \InvalidArgumentException("Unsupported AI provider [{$driver}]."),
        };
    }
}
