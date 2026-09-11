<?php

namespace Sharifuddin\LaravelAiBridge\VectorStore;

use Illuminate\Support\Facades\DB;
use Sharifuddin\LaravelAiBridge\Contracts\VectorStoreInterface;
use Sharifuddin\LaravelAiBridge\DTO\VectorRecord;
use Sharifuddin\LaravelAiBridge\DTO\VectorSearchResult;
use Sharifuddin\LaravelAiBridge\Exceptions\VectorStoreException;

/**
 * Default production vector store: PostgreSQL + the `pgvector` extension.
 *
 * Requires:
 *   - A Postgres connection (config('database.connections.*') - reuses
 *     the app's default connection unless ai-bridge.vector.postgresql.connection
 *     names another one).
 *   - `CREATE EXTENSION vector;` privileges. The store lazily creates the
 *     extension and its table on first use, so no manual migration is
 *     required to get started.
 *
 * Uses pgvector's `<=>` cosine-distance operator for search, so
 * similarity = 1 - distance.
 */
final class PostgreSQLVectorStore implements VectorStoreInterface
{
    private string $table;

    private int $dimensions;

    private \Illuminate\Database\Connection $db;

    public function __construct()
    {
        $connectionName = config('ai-bridge.vector.postgresql.connection');
        $this->table = (string) config('ai-bridge.vector.postgresql.table', 'ai_tool_vectors');
        $this->dimensions = (int) config('ai-bridge.vector.postgresql.dimensions', 768);

        try {
            $this->db = DB::connection($connectionName);

            if ($this->db->getDriverName() !== 'pgsql') {
                throw new \RuntimeException("Connection [{$connectionName}] is not a PostgreSQL connection.");
            }

            $this->ensureSchema();
        } catch (\Throwable $e) {
            throw new VectorStoreException(
                'Unable to initialize the PostgreSQL/pgvector vector store: ' . $e->getMessage() .
                '. Ensure a PostgreSQL connection is configured and the pgvector extension is installed, ' .
                'or set AI_VECTOR_STORE=array / mysql / mongodb as an alternative.',
                previous: $e
            );
        }
    }

    private function ensureSchema(): void
    {
        $this->db->statement('CREATE EXTENSION IF NOT EXISTS vector');

        $table = $this->table;
        $dims = $this->dimensions;

        $this->db->statement(<<<SQL
            CREATE TABLE IF NOT EXISTS "{$table}" (
                id VARCHAR(191) PRIMARY KEY,
                embedding VECTOR({$dims}) NOT NULL,
                metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
                text TEXT NOT NULL DEFAULT '',
                hash VARCHAR(64) NOT NULL DEFAULT '',
                updated_at TIMESTAMP NOT NULL DEFAULT now()
            )
        SQL);
    }

    public function upsert(VectorRecord $record): void
    {
        $vectorLiteral = $this->toVectorLiteral($record->vector);
        $metadataJson = json_encode($record->metadata, JSON_THROW_ON_ERROR);

        try {
            $this->db->statement(
                'INSERT INTO "' . $this->table . '" (id, embedding, metadata, text, hash, updated_at) ' .
                'VALUES (?, ?::vector, ?::jsonb, ?, ?, now()) ' .
                'ON CONFLICT (id) DO UPDATE SET embedding = excluded.embedding, metadata = excluded.metadata, ' .
                'text = excluded.text, hash = excluded.hash, updated_at = excluded.updated_at',
                [$record->id, $vectorLiteral, $metadataJson, $record->text, $record->hash]
            );
        } catch (\Throwable $e) {
            throw new VectorStoreException('PostgreSQL upsert failed: ' . $e->getMessage(), previous: $e);
        }
    }

    public function delete(string $id): void
    {
        try {
            $this->db->table($this->table)->where('id', $id)->delete();
        } catch (\Throwable $e) {
            throw new VectorStoreException('PostgreSQL delete failed: ' . $e->getMessage(), previous: $e);
        }
    }

    public function search(array $vector, int $topK, array $filters = []): array
    {
        $vectorLiteral = $this->toVectorLiteral($vector);

        $whereClauses = [];
        $bindings = [$vectorLiteral];

        foreach ($filters as $key => $value) {
            $whereClauses[] = 'metadata ->> ? = ?';
            $bindings[] = $key;
            $bindings[] = (string) $value;
        }

        $where = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
        $bindings[] = $vectorLiteral;
        $bindings[] = $topK;

        try {
            $rows = $this->db->select(
                'SELECT id, metadata, 1 - (embedding <=> ?::vector) AS score FROM "' . $this->table . '" ' .
                $where . ' ORDER BY embedding <=> ?::vector ASC LIMIT ?',
                $bindings
            );
        } catch (\Throwable $e) {
            throw new VectorStoreException('PostgreSQL vector search failed: ' . $e->getMessage(), previous: $e);
        }

        return array_map(function ($row) {
            $metadata = is_string($row->metadata) ? json_decode($row->metadata, true) : (array) $row->metadata;

            return new VectorSearchResult((string) $row->id, (float) $row->score, $metadata ?: []);
        }, $rows);
    }

    public function searchMany(array $vectors, int $topK, array $filters = []): array
    {
        $results = [];
        foreach ($vectors as $key => $vector) {
            $results[$key] = $this->search($vector, $topK, $filters);
        }

        return $results;
    }

    /** @param array<int, float> $vector */
    private function toVectorLiteral(array $vector): string
    {
        return '[' . implode(',', array_map(fn ($v) => (string) (float) $v, $vector)) . ']';
    }
}
