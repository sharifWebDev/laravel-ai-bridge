<?php

namespace Sharifuddin\LaravelAiBridge\VectorStore;

use Illuminate\Support\Facades\Log;
use Sharifuddin\LaravelAiBridge\Contracts\VectorStoreInterface;
use Sharifuddin\LaravelAiBridge\Exceptions\VectorStoreException;

/**
 * Resolves the configured vector store driver (AI_VECTOR_STORE /
 * ai-bridge.vector.driver). "postgresql" (pgvector) is the documented
 * default; "mysql", "mongodb", "qdrant" and "pinecone" are selectable
 * alternatives, and "array" is the dependency-free fallback used for
 * local development, tests, and graceful degradation.
 *
 * If the selected driver can't be initialized (missing extension,
 * unreachable server, missing config, ...), the manager automatically
 * falls back to ArrayVectorStore unless ai-bridge.vector.fallback_to_array
 * is explicitly disabled - so a fresh install is always usable, and a
 * temporarily-unavailable vector database degrades gracefully instead of
 * hard-failing every chat request.
 */
final class VectorStoreManager
{
    public static function resolve(): VectorStoreInterface
    {
        $driver = strtolower((string) config('ai-bridge.vector.driver', 'postgresql'));

        try {
            return match ($driver) {
                'array' => new ArrayVectorStore(),
                'postgresql', 'postgres', 'pgvector', 'pgsql' => new PostgreSQLVectorStore(),
                'mysql', 'mariadb' => new MySQLVectorStore(),
                'mongodb', 'mongo' => new MongoVectorStore(),
                'qdrant' => new QdrantVectorStore(),
                'pinecone' => new PineconeVectorStore(),
                default => throw new VectorStoreException("Unsupported vector store driver [{$driver}]."),
            };
        } catch (VectorStoreException $e) {
            if ($driver !== 'array' && config('ai-bridge.vector.fallback_to_array', true)) {
                Log::warning('[ai-bridge] Falling back to the array vector store: ' . $e->getMessage());

                return new ArrayVectorStore();
            }

            throw $e;
        }
    }
}
