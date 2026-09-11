<?php

namespace Sharifuddin\LaravelAiBridge\VectorStore;

use Illuminate\Support\Facades\Cache;
use Sharifuddin\LaravelAiBridge\Contracts\VectorStoreInterface;
use Sharifuddin\LaravelAiBridge\DTO\VectorRecord;
use Sharifuddin\LaravelAiBridge\DTO\VectorSearchResult;

/**
 * Dependency-free vector store backed by Laravel's cache. This is the
 * package's built-in fallback: it requires no MongoDB/Atlas setup, so the
 * package works out of the box for development, testing, and as a
 * graceful-degradation target when the configured production vector
 * store (e.g. Mongo) is temporarily unavailable. Not recommended at
 * large tool-catalog scale - swap to Mongo/Pinecone/Qdrant/etc. via
 * config for production.
 */
final class ArrayVectorStore implements VectorStoreInterface
{
    private const INDEX_KEY = 'ai-bridge:vector:array:index';

    public function upsert(VectorRecord $record): void
    {
        $index = $this->readIndex();
        $index[$record->id] = $record->toArray();
        $this->writeIndex($index);
    }

    public function delete(string $id): void
    {
        $index = $this->readIndex();
        unset($index[$id]);
        $this->writeIndex($index);
    }

    public function search(array $vector, int $topK, array $filters = []): array
    {
        $index = $this->readIndex();
        $scored = [];

        foreach ($index as $id => $record) {
            if (!$this->matchesFilters($record['metadata'] ?? [], $filters)) {
                continue;
            }

            $score = $this->cosineSimilarity($vector, $record['vector']);
            $scored[] = new VectorSearchResult($id, $score, $record['metadata'] ?? []);
        }

        usort($scored, fn (VectorSearchResult $a, VectorSearchResult $b) => $b->score <=> $a->score);

        return array_slice($scored, 0, $topK);
    }

    public function searchMany(array $vectors, int $topK, array $filters = []): array
    {
        $results = [];
        foreach ($vectors as $key => $vector) {
            $results[$key] = $this->search($vector, $topK, $filters);
        }

        return $results;
    }

    /** @return array<string, array<string, mixed>> */
    private function readIndex(): array
    {
        return Cache::get(self::INDEX_KEY, []);
    }

    /** @param array<string, array<string, mixed>> $index */
    private function writeIndex(array $index): void
    {
        // The tool catalog index has no natural expiry - it is only ever
        // replaced by re-indexing, so it is stored without a TTL.
        Cache::forever(self::INDEX_KEY, $index);
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $filters
     */
    private function matchesFilters(array $metadata, array $filters): bool
    {
        foreach ($filters as $key => $value) {
            if (($metadata[$key] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, float> $a
     * @param array<int, float> $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        $length = min(count($a), count($b));
        if ($length === 0) {
            return 0.0;
        }

        $dot = $normA = $normB = 0.0;
        for ($i = 0; $i < $length; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
