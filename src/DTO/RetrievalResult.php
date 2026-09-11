<?php

namespace Sharifuddin\LaravelAiBridge\DTO;

/**
 * Outcome of a single retrieve() call: the ranked, filtered top-K
 * candidates plus confidence/ambiguity flags the caller can use to decide
 * whether to proceed with tool declarations or ask the user to clarify.
 */
final class RetrievalResult
{
    /** @param array<int, RetrievalCandidate> $candidates */
    public function __construct(
        public readonly string $normalizedQuery,
        public readonly array $candidates,
        public readonly bool $isAmbiguous,
        public readonly bool $isLowConfidence,
        public readonly bool $usedVectorSearch,
    ) {
    }

    public function topScore(): float
    {
        return $this->candidates[0]->finalScore ?? 0.0;
    }

    public function isEmpty(): bool
    {
        return $this->candidates === [];
    }

    /** @return array<int, array<string, mixed>> */
    public function toArray(): array
    {
        return array_map(fn (RetrievalCandidate $c) => $c->toArray(), $this->candidates);
    }
}
