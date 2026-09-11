<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Fakes;

use Sharifuddin\LaravelAiBridge\Contracts\EmbeddingProviderInterface;

/**
 * Deterministic "embedding" for tests: maps known phrases to hand-picked
 * vectors so retrieval ranking is fully predictable, without any network
 * calls or real embedding model.
 */
class FakeEmbeddingProvider implements EmbeddingProviderInterface
{
    /** @var array<string, array<int, float>> */
    public static array $vectors = [];

    public int $callCount = 0;

    public function embed(string $text): array
    {
        $this->callCount++;

        foreach (self::$vectors as $needle => $vector) {
            if (str_contains($text, $needle)) {
                return $vector;
            }
        }

        return [0.0, 0.0, 0.0];
    }

    public function identifier(): string
    {
        return 'fake:test';
    }

    public function dimensions(): int
    {
        return 3;
    }
}
