<?php

namespace Sharifuddin\LaravelAiBridge\Retrieval;

final class NormalizedQuery
{
    /** @param array<int, string> $tokens */
    public function __construct(
        public readonly string $original,
        public readonly string $normalized,
        public readonly array $tokens,
    ) {
    }
}
