<?php

namespace Sharifuddin\LaravelAiBridge\Embeddings;

use Sharifuddin\LaravelAiBridge\Contracts\EmbeddingProviderInterface;

/**
 * Deterministic, dependency-free "embedding" for local development, unit
 * tests, and CI - anywhere a real embedding API key/network isn't
 * available. It hashes overlapping character shingles into a fixed-size
 * float vector, which gives lexically-similar strings similar vectors.
 * It is NOT semantically meaningful and must never be relied on for
 * production retrieval quality - use the "gemini" (or another real)
 * driver in production.
 */
final class HashEmbeddingProvider implements EmbeddingProviderInterface
{
    public function __construct(private readonly int $dims = 128)
    {
    }

    public function embed(string $text): array
    {
        $vector = array_fill(0, $this->dims, 0.0);
        $normalized = mb_strtolower(trim($text));
        $length = mb_strlen($normalized);

        $shingleSize = 3;
        for ($i = 0; $i < $length; $i++) {
            $shingle = mb_substr($normalized, $i, $shingleSize);
            if ($shingle === '') {
                continue;
            }
            $bucket = crc32($shingle) % $this->dims;
            $vector[$bucket] += 1.0;
        }

        $norm = sqrt(array_sum(array_map(fn ($v) => $v * $v, $vector)));
        if ($norm > 0) {
            $vector = array_map(fn ($v) => $v / $norm, $vector);
        }

        return $vector;
    }

    public function identifier(): string
    {
        return "hash:{$this->dims}";
    }

    public function dimensions(): int
    {
        return $this->dims;
    }
}
