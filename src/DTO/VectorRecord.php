<?php

namespace Sharifuddin\LaravelAiBridge\DTO;

/**
 * A single embedded document to be stored in a VectorStoreInterface
 * implementation: the tool's id (its canonical name), its embedding
 * vector, filterable metadata, and the source text (kept for debugging /
 * re-indexing decisions, never sensitive).
 */
final class VectorRecord
{
    /**
     * @param array<int, float> $vector
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly string $id,
        public readonly array $vector,
        public readonly array $metadata,
        public readonly string $text,
        public readonly string $hash,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'vector' => $this->vector,
            'metadata' => $this->metadata,
            'text' => $this->text,
            'hash' => $this->hash,
        ];
    }
}
