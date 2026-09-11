<?php

namespace Sharifuddin\LaravelAiBridge\Embeddings;

use Sharifuddin\LaravelAiBridge\Contracts\EmbeddingProviderInterface;

/**
 * Resolves the configured embedding driver (ai-bridge.embedding.driver)
 * and wraps it with the persistent EmbeddingCache decorator. Add new
 * drivers by extending the match() below - business logic never depends
 * on a concrete provider class.
 */
final class EmbeddingManager
{
    public static function resolve(): EmbeddingProviderInterface
    {
        $driver = (string) config('ai-bridge.embedding.driver', 'gemini');

        $provider = match ($driver) {
            'hash' => new HashEmbeddingProvider((int) config('ai-bridge.embedding.dimensions', 128)),
            'gemini' => new GeminiEmbeddingProvider(),
            default => throw new \InvalidArgumentException("Unsupported embedding driver [{$driver}]."),
        };

        return new EmbeddingCache($provider);
    }
}
