<?php

namespace Sharifuddin\LaravelAiBridge\Embeddings;

use Illuminate\Support\Facades\Cache;
use Sharifuddin\LaravelAiBridge\Caching\CacheKeyBuilder;
use Sharifuddin\LaravelAiBridge\Contracts\EmbeddingProviderInterface;
use Sharifuddin\LaravelAiBridge\Events\CacheHit;
use Sharifuddin\LaravelAiBridge\Events\CacheMiss;
use Sharifuddin\LaravelAiBridge\Events\EmbeddingGenerated;

/**
 * Decorates any EmbeddingProviderInterface with a persistent, deterministic
 * cache keyed on hash(embedding_model + normalized_text), so the same
 * semantic text never generates a new embedding (and never incurs a new
 * API call/cost) twice.
 */
final class EmbeddingCache implements EmbeddingProviderInterface
{
    public function __construct(private readonly EmbeddingProviderInterface $inner)
    {
    }

    public function embed(string $text): array
    {
        if (!config('ai-bridge.cache.enabled', true)) {
            return $this->inner->embed($text);
        }

        $store = config('ai-bridge.cache.store');
        $ttl = (int) config('ai-bridge.cache.ttls.embedding', 604800);
        $key = CacheKeyBuilder::embedding($this->inner->identifier(), $text);

        $repository = $store ? Cache::store($store) : Cache::store();

        if ($repository->has($key)) {
            event(new CacheHit('embedding', $key));

            return $repository->get($key);
        }

        event(new CacheMiss('embedding', $key));

        $start = microtime(true);
        $vector = $this->inner->embed($text);
        $durationMs = (microtime(true) - $start) * 1000;

        event(new EmbeddingGenerated($this->inner->identifier(), mb_strlen($text), $durationMs));

        $repository->put($key, $vector, $ttl);

        return $vector;
    }

    public function identifier(): string
    {
        return $this->inner->identifier();
    }

    public function dimensions(): int
    {
        return $this->inner->dimensions();
    }
}
