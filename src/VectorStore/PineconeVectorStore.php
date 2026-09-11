<?php

namespace Sharifuddin\LaravelAiBridge\VectorStore;

use Illuminate\Support\Facades\Http;
use Sharifuddin\LaravelAiBridge\Contracts\VectorStoreInterface;
use Sharifuddin\LaravelAiBridge\DTO\VectorRecord;
use Sharifuddin\LaravelAiBridge\DTO\VectorSearchResult;
use Sharifuddin\LaravelAiBridge\Exceptions\VectorStoreException;

/**
 * Pinecone vector store, driven over Pinecone's REST API (no extra
 * composer dependency required). Unlike Qdrant, Pinecone accepts
 * arbitrary string vector IDs, so tool names are used directly.
 *
 * Requires an existing Pinecone index (index creation/dimension
 * configuration happens in the Pinecone console/API, not here) and its
 * per-index host URL.
 */
final class PineconeVectorStore implements VectorStoreInterface
{
    private string $host;

    private string $apiKey;

    private string $namespace;

    public function __construct()
    {
        $this->host = rtrim((string) config('ai-bridge.vector.pinecone.host', ''), '/');
        $this->apiKey = (string) config('ai-bridge.vector.pinecone.api_key', '');
        $this->namespace = (string) config('ai-bridge.vector.pinecone.namespace', 'ai-bridge-tools');

        if (empty($this->host) || empty($this->apiKey)) {
            throw new VectorStoreException(
                'Pinecone vector store requires ai-bridge.vector.pinecone.host and .api_key to be configured.'
            );
        }
    }

    private function client()
    {
        return Http::baseUrl($this->host)
            ->timeout(10)
            ->acceptJson()
            ->withHeaders(['Api-Key' => $this->apiKey]);
    }

    public function upsert(VectorRecord $record): void
    {
        try {
            $response = $this->client()->post('/vectors/upsert', [
                'vectors' => [[
                    'id' => $record->id,
                    'values' => $record->vector,
                    'metadata' => array_merge($record->metadata, [
                        'text' => $record->text,
                        'hash' => $record->hash,
                    ]),
                ]],
                'namespace' => $this->namespace,
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException('HTTP ' . $response->status() . ': ' . $response->body());
            }
        } catch (\Throwable $e) {
            throw new VectorStoreException('Pinecone upsert failed: ' . $e->getMessage(), previous: $e);
        }
    }

    public function delete(string $id): void
    {
        try {
            $this->client()->post('/vectors/delete', [
                'ids' => [$id],
                'namespace' => $this->namespace,
            ]);
        } catch (\Throwable $e) {
            throw new VectorStoreException('Pinecone delete failed: ' . $e->getMessage(), previous: $e);
        }
    }

    public function search(array $vector, int $topK, array $filters = []): array
    {
        $body = [
            'vector' => $vector,
            'topK' => $topK,
            'includeMetadata' => true,
            'namespace' => $this->namespace,
        ];

        if (!empty($filters)) {
            $filter = [];
            foreach ($filters as $key => $value) {
                $filter[$key] = ['$eq' => $value];
            }
            $body['filter'] = $filter;
        }

        try {
            $response = $this->client()->post('/query', $body);

            if (!$response->successful()) {
                throw new \RuntimeException('HTTP ' . $response->status() . ': ' . $response->body());
            }
        } catch (\Throwable $e) {
            throw new VectorStoreException('Pinecone search failed: ' . $e->getMessage(), previous: $e);
        }

        $results = [];
        foreach ($response->json('matches', []) as $match) {
            $metadata = $match['metadata'] ?? [];
            unset($metadata['text'], $metadata['hash']);

            $results[] = new VectorSearchResult((string) $match['id'], (float) ($match['score'] ?? 0.0), $metadata);
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
