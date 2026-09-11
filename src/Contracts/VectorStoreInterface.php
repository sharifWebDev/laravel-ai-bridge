<?php

namespace Sharifuddin\LaravelAiBridge\Contracts;

use Sharifuddin\LaravelAiBridge\DTO\VectorRecord;
use Sharifuddin\LaravelAiBridge\DTO\VectorSearchResult;

/**
 * Storage-agnostic vector search abstraction. MongoDB Atlas Vector Search
 * is the default production implementation; ArrayVectorStore is a
 * dependency-free fallback for local development, tests, and graceful
 * degradation. Future adapters (Pinecone, Qdrant, Weaviate, pgvector) only
 * need to implement this interface.
 */
interface VectorStoreInterface
{
    public function upsert(VectorRecord $record): void;

    public function delete(string $id): void;

    /**
     * @param array<int, float> $vector
     * @param array<string, mixed> $filters metadata pre-filters (e.g. category)
     * @return array<int, VectorSearchResult>
     */
    public function search(array $vector, int $topK, array $filters = []): array;

    /**
     * Batch variant of search(). Keys of the returned array match the keys
     * of $vectors.
     *
     * @param array<string|int, array<int, float>> $vectors
     * @param array<string, mixed> $filters
     * @return array<string|int, array<int, VectorSearchResult>>
     */
    public function searchMany(array $vectors, int $topK, array $filters = []): array;
}
