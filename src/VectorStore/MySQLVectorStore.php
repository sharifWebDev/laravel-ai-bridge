<?php

namespace Sharifuddin\LaravelAiBridge\VectorStore;

use Illuminate\Support\Facades\DB;
use Sharifuddin\LaravelAiBridge\Contracts\VectorStoreInterface;
use Sharifuddin\LaravelAiBridge\DTO\VectorRecord;
use Sharifuddin\LaravelAiBridge\DTO\VectorSearchResult;
use Sharifuddin\LaravelAiBridge\Exceptions\VectorStoreException;

/**
 * MySQL vector store: works on stock MySQL/MariaDB with no extension or
 * plugin required. Vectors are stored as a JSON column and similarity is
 * computed in PHP (application-level cosine similarity, same algorithm as
 * ArrayVectorStore) rather than pushed down to the database - this trades
 * scale for zero-dependency portability. Fine for typical tool catalogs
 * (tens to low hundreds of tools); for larger catalogs prefer the
 * "postgresql" (pgvector) or "mongodb" (Atlas Vector Search) drivers.
 */
final class MySQLVectorStore implements VectorStoreInterface
{
    private string $table;

    private \Illuminate\Database\Connection $db;

    public function __construct()
    {
        $connectionName = config('ai-bridge.vector.mysql.connection');
        $this->table = (string) config('ai-bridge.vector.mysql.table', 'ai_tool_vectors');

        try {
            $this->db = DB::connection($connectionName);

            if (!in_array($this->db->getDriverName(), ['mysql', 'mariadb'], true)) {
                throw new \RuntimeException("Connection [{$connectionName}] is not a MySQL/MariaDB connection.");
            }

            $this->ensureSchema();
        } catch (\Throwable $e) {
            throw new VectorStoreException(
                'Unable to initialize the MySQL vector store: ' . $e->getMessage() .
                '. Ensure a MySQL/MariaDB connection is configured, or set AI_VECTOR_STORE=array / postgresql / mongodb as an alternative.',
                previous: $e
            );
        }
    }

    private function ensureSchema(): void
    {
        if ($this->db->getSchemaBuilder()->hasTable($this->table)) {
            return;
        }

        $this->db->getSchemaBuilder()->create($this->table, function ($table) {
            $table->string('id', 191)->primary();
            $table->json('embedding');
            $table->json('metadata')->nullable();
            $table->text('text')->nullable();
            $table->string('hash', 64)->nullable();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function upsert(VectorRecord $record): void
    {
        try {
            $this->db->table($this->table)->updateOrInsert(
                ['id' => $record->id],
                [
                    'embedding' => json_encode($record->vector, JSON_THROW_ON_ERROR),
                    'metadata' => json_encode($record->metadata, JSON_THROW_ON_ERROR),
                    'text' => $record->text,
                    'hash' => $record->hash,
                    'updated_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            throw new VectorStoreException('MySQL upsert failed: ' . $e->getMessage(), previous: $e);
        }
    }

    public function delete(string $id): void
    {
        try {
            $this->db->table($this->table)->where('id', $id)->delete();
        } catch (\Throwable $e) {
            throw new VectorStoreException('MySQL delete failed: ' . $e->getMessage(), previous: $e);
        }
    }

    public function search(array $vector, int $topK, array $filters = []): array
    {
        try {
            $query = $this->db->table($this->table);

            foreach ($filters as $key => $value) {
                $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(metadata, ?)) = ?', ['$."' . $key . '"', (string) $value]);
            }

            $rows = $query->get(['id', 'embedding', 'metadata']);
        } catch (\Throwable $e) {
            throw new VectorStoreException('MySQL vector search failed: ' . $e->getMessage(), previous: $e);
        }

        $scored = [];
        foreach ($rows as $row) {
            $rowVector = json_decode($row->embedding, true) ?: [];
            $metadata = json_decode($row->metadata ?? '{}', true) ?: [];

            $scored[] = new VectorSearchResult((string) $row->id, $this->cosineSimilarity($vector, $rowVector), $metadata);
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
