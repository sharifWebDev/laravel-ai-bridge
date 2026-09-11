<?php

namespace Sharifuddin\LaravelAiBridge\Contracts;

/**
 * Turns text into a numeric vector. Kept independent from the chat
 * AiProviderInterface so embedding and generation models can be swapped
 * separately (e.g. Gemini for chat, a local model for embeddings).
 */
interface EmbeddingProviderInterface
{
    /** @return array<int, float> */
    public function embed(string $text): array;

    /** Identifier used in cache keys, e.g. "gemini:text-embedding-004". */
    public function identifier(): string;

    /** Vector dimensionality produced by this provider. */
    public function dimensions(): int;
}
