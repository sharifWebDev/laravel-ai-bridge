<?php

namespace Sharifuddin\LaravelAiBridge\DTO;

final class VectorSearchResult
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public readonly string $id,
        public readonly float $score,
        public readonly array $metadata = [],
    ) {
    }
}
