<?php

namespace Sharifuddin\LaravelAiBridge\VectorStore;

use Sharifuddin\LaravelAiBridge\Contracts\VectorStoreInterface;
use Sharifuddin\LaravelAiBridge\DTO\VectorRecord;
use Sharifuddin\LaravelAiBridge\DTO\VectorSearchResult;
use Sharifuddin\LaravelAiBridge\Exceptions\VectorStoreException;

/**
 * Optional vector store driver: MongoDB Atlas Vector Search
 * (AI_VECTOR_STORE=mongodb). "postgresql" (pgvector) is the package
 * default; this driver is for teams already standardized on MongoDB.
 *
 * Requires the `mongodb/mongodb` composer package and the `ext-mongodb`
 * PHP extension (neither is a hard dependency of this package, so
 * installations that only use the "array" driver don't need them). An
 * Atlas Vector Search index must already exist on the target collection,
 * e.g.:
 *
 *   {
 *     "fields": [
 *       { "type": "vector", "path": "vector", "numDimensions": 768, "similarity": "cosine" },
 *       { "type": "filter", "path": "metadata.category" },
 *       { "type": "filter", "path": "metadata.permission" }
 *     ]
 *   }
 *
 * created with the name configured in ai-bridge.vector.mongodb.index_name.
 */
final class MongoVectorStore implements VectorStoreInterface
{
    private \MongoDB\Collection $collection;

    private string $indexName;

    public function __construct()
    {
        if (!class_exists(\MongoDB\Client::class)) {
            throw new VectorStoreException(
                'The mongodb/mongodb composer package (and ext-mongodb) must be installed to use the "mongo" ' .
                'vector store driver. Run `composer require mongodb/mongodb` or set AI_BRIDGE_VECTOR_DRIVER=array.'
            );
        }

        $uri = (string) config('ai-bridge.vector.mongodb.uri', 'mongodb://127.0.0.1:27017');
        $database = (string) config('ai-bridge.vector.mongodb.database', 'ai_bridge');
        $collection = (string) config('ai-bridge.vector.mongodb.collection', 'ai_tool_vectors');
        $this->indexName = (string) config('ai-bridge.vector.mongodb.index_name', 'ai_tool_vector_index');

        try {
            $client = new \MongoDB\Client($uri);
            $this->collection = $client->selectCollection($database, $collection);
        } catch (\Throwable $e) {
            throw new VectorStoreException('Unable to connect to MongoDB: ' . $e->getMessage(), previous: $e);
        }
    }

    public function upsert(VectorRecord $record): void
    {
        try {
            $this->collection->replaceOne(
                ['_id' => $record->id],
                [
                    '_id' => $record->id,
                    'vector' => $record->vector,
                    'metadata' => $record->metadata,
                    'text' => $record->text,
                    'hash' => $record->hash,
                    'updated_at' => new \MongoDB\BSON\UTCDateTime(),
                ],
                ['upsert' => true]
            );
        } catch (\Throwable $e) {
            throw new VectorStoreException('MongoDB upsert failed: ' . $e->getMessage(), previous: $e);
        }
    }

    public function delete(string $id): void
    {
        try {
            $this->collection->deleteOne(['_id' => $id]);
        } catch (\Throwable $e) {
            throw new VectorStoreException('MongoDB delete failed: ' . $e->getMessage(), previous: $e);
        }
    }

    public function search(array $vector, int $topK, array $filters = []): array
    {
        $mongoFilter = [];
        foreach ($filters as $key => $value) {
            $mongoFilter["metadata.{$key}"] = $value;
        }

        $pipeline = [
            [
                '$vectorSearch' => array_filter([
                    'index' => $this->indexName,
                    'path' => 'vector',
                    'queryVector' => $vector,
                    'numCandidates' => max($topK * 10, 100),
                    'limit' => $topK,
                    'filter' => $mongoFilter ?: null,
                ]),
            ],
            [
                '$project' => [
                    '_id' => 1,
                    'metadata' => 1,
                    'score' => ['$meta' => 'vectorSearchScore'],
                ],
            ],
        ];

        try {
            $cursor = $this->collection->aggregate($pipeline);
        } catch (\Throwable $e) {
            throw new VectorStoreException('MongoDB Atlas Vector Search query failed: ' . $e->getMessage(), previous: $e);
        }

        $results = [];
        foreach ($cursor as $doc) {
            $results[] = new VectorSearchResult(
                (string) $doc['_id'],
                (float) $doc['score'],
                isset($doc['metadata']) ? (array) $doc['metadata'] : []
            );
        }

        return $results;
    }

    public function searchMany(array $vectors, int $topK, array $filters = []): array
    {
        $results = [];
        foreach ($vectors as $key => $vector) {
            $results[$key] = $this->search($vector, $topK, $filters);
        }

        return $results;
    }
}
