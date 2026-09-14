<?php

namespace Sharifuddin\LaravelAiBridge\Providers;

use Sharifuddin\LaravelAiBridge\Contracts\AiProviderInterface;

/**
 * Resolves the configured chat/generation provider (ai-bridge.provider).
 *
 * Switching AI agents is a config/env-only change:
 *
 *   AI_BRIDGE_PROVIDER=gemini      (default, ai-bridge.gemini.*)
 *   AI_BRIDGE_PROVIDER=openai      (ChatGPT, ai-bridge.openai.*)
 *   AI_BRIDGE_PROVIDER=chatgpt     (alias of openai)
 *   AI_BRIDGE_PROVIDER=deepseek    (ai-bridge.deepseek.*)
 *   AI_BRIDGE_PROVIDER=anthropic   (Claude, ai-bridge.anthropic.*)
 *
 * Any other value is looked up in ai-bridge.providers.{key}:
 *   - if it has a "class" entry, that class is instantiated directly
 *     (must implement AiProviderInterface) - for a fully custom provider
 *     with its own wire format;
 *   - otherwise it's treated as a generic OpenAI-compatible API (Groq,
 *     OpenRouter, Together, a local/self-hosted model, or literally any
 *     other "custom AI api") as long as it exposes base_url/key/model.
 */
final class AiProviderManager
{
    public static function resolve(): AiProviderInterface
    {
        $driver = strtolower((string) config('ai-bridge.provider', 'gemini'));

        return match ($driver) {
            'gemini' => new GeminiAiProvider(),
            'openai', 'chatgpt', 'gpt' => new OpenAiCompatibleProvider(
                (array) config('ai-bridge.openai', []),
                'openai'
            ),
            'deepseek' => new OpenAiCompatibleProvider(
                (array) config('ai-bridge.deepseek', []),
                'deepseek'
            ),
            'anthropic', 'claude' => new AnthropicAiProvider((array) config('ai-bridge.anthropic', [])),
            default => self::resolveCustom($driver),
        };
    }

    /**
     * Resolves any provider registered under ai-bridge.providers.{$driver}
     * that isn't one of the built-in keys above - this is the extension
     * point for "add any other custom AI API" without touching this class.
     */
    private static function resolveCustom(string $driver): AiProviderInterface
    {
        $config = config("ai-bridge.providers.{$driver}");

        if (!is_array($config)) {
            throw new \InvalidArgumentException(
                "Unsupported AI provider [{$driver}]. Add it under config('ai-bridge.providers.{$driver}') " .
                "with either a 'class' (a fully custom AiProviderInterface implementation) or " .
                "'base_url' / 'key' / 'model' (for any OpenAI-compatible API)."
            );
        }

        if (!empty($config['class'])) {
            $class = $config['class'];

            if (!class_exists($class) || (!is_subclass_of($class, AiProviderInterface::class) && $class !== AiProviderInterface::class)) {
                throw new \InvalidArgumentException("Custom AI provider class [{$class}] for driver [{$driver}] must exist and implement AiProviderInterface.");
            }

            return function_exists('app') ? app()->make($class, ['config' => $config]) : new $class($config);
        }

        // No custom class given - assume it's any other OpenAI-compatible
        // custom AI API (this is the common case: Groq, OpenRouter,
        // Together, a self-hosted/local model, an internal proxy, etc.).
        return new OpenAiCompatibleProvider($config, $driver);
    }
}
