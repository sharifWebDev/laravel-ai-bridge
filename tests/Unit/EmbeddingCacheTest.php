<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Unit;

use Sharifuddin\LaravelAiBridge\Embeddings\EmbeddingCache;
use Sharifuddin\LaravelAiBridge\Tests\Fakes\FakeEmbeddingProvider;
use Sharifuddin\LaravelAiBridge\Tests\TestCase;

class EmbeddingCacheTest extends TestCase
{
    public function test_same_text_does_not_regenerate_embedding(): void
    {
        FakeEmbeddingProvider::$vectors = ['hello' => [1.0, 0.0, 0.0]];
        $inner = new FakeEmbeddingProvider();
        $cache = new EmbeddingCache($inner);

        $cache->embed('hello world');
        $cache->embed('hello world');
        $cache->embed('hello world');

        $this->assertSame(1, $inner->callCount);
    }

    public function test_different_text_regenerates_embedding(): void
    {
        FakeEmbeddingProvider::$vectors = ['hello' => [1.0, 0.0, 0.0], 'goodbye' => [0.0, 1.0, 0.0]];
        $inner = new FakeEmbeddingProvider();
        $cache = new EmbeddingCache($inner);

        $cache->embed('hello world');
        $cache->embed('goodbye world');

        $this->assertSame(2, $inner->callCount);
    }

    public function test_cache_disabled_always_hits_inner_provider(): void
    {
        config(['ai-bridge.cache.enabled' => false]);
        FakeEmbeddingProvider::$vectors = ['hello' => [1.0, 0.0, 0.0]];
        $inner = new FakeEmbeddingProvider();
        $cache = new EmbeddingCache($inner);

        $cache->embed('hello world');
        $cache->embed('hello world');

        $this->assertSame(2, $inner->callCount);
    }
}
