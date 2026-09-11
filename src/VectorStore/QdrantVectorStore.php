<?php

namespace Sharifuddin\LaravelAiBridge\VectorStore;

use Illuminate\Support\Facades\Http;
use Sharifuddin\LaravelAiBridge\Contracts\VectorStoreInterface;
use Sharifuddin\LaravelAiBridge\DTO\VectorRecord;
use Sharifuddin\LaravelAiBridge\DTO\VectorSearchResult;
use Sharifuddin\LaravelAiBridge\Exceptions\VectorStoreException;

/**
 * Qdrant vector store, driven entirely over Qdrant's REST API (no extra
 * composer dependency required). Qdrant point IDs must be an unsigned
 * integer or a UUID, so tool names are mapped to a deterministic
 * UUID-formatted ID; the original tool name travels in the point payload
 * and is what search() actually returns as VectorSearchResult::$id.
 */
final class QdrantVectorStore implements VectorStoreInterface
{
    private string $baseUrl;

    private ?string $apiKey;

    private string $collection;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('ai-bridge.vector.qdrant.base_url', 'http://127.0.0.1:6333'), '/');
        $this->apiKey = config('ai-bridge.vector.qdrant.api_key');
        $this->collection = (string) config('ai-bridge.vector.qdrant.collection', 'ai_tool_vectors');

        if (empty($this->baseUrl)) {
            throw new VectorStoreException('Qdrant vector store requires ai-bridge.vector.qdrant.base_url to be configured.');
        }

        $this->ensureCollection();
    }

    private function client()
    {
        $client = Http::baseUrl($this->baseUrl)->timeout(10)->acceptJson();

        return $this->apiKey ? $client->withHeaders(['api-key' => $this->apiKey]) : $client;
    }

    private function ensureCollection(): void
    {
        try {
            $exists = $this->client()->get("/collections/{$this->collection}");

            if ($exists->successful()) {
                return;
            }

            $dims = (int) config('ai-bridge.vector.qdrant.dimensions', 768);
            $distance = (string) config('ai-bridge.vector.qdrant.distance', 'Cosine');

            $created = $this->client()->put("/collections/{$this->collection}", [
                'vectors' => ['size' => $dims, 'distance' => $distance],
            ]);

            if (!$created->successful()) {
                throw new \RuntimeException('Qdrant collection creation failed: HTTP ' . $created->status());
            }
        } catch (\Throwable $e) {
            throw new VectorStoreException(
                'Unable to reach/initialize Qdrant at ' . $this->baseUrl . ': ' . $e->getMessage() .
                '. Set AI_VECTOR_STORE=array / postgresql / mysql / mongodb as an alternative.',
                previous: $e
            );
        }
    }

    public function upsert(VectorRecord $record): void
    {
        $pointId = $this->toPointId($record->id);

        try {
            $response = $this->client()->put("/collections/{$this->collection}/points", [
                'points' => [[
                    'id' => $pointId,
                    'vector' => $record->vector,
                    'payload' => array_merge($record->metadata, [
                        'tool_name' => $record->id,
                        'text' => $record->text,
                        'hash' => $record->hash,
                    ]),
                ]],
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException('HTTP ' . $response->status() . ': ' . $response->body());
            }
        } catch (\Throwable $e) {
            throw new VectorStoreException('Qdrant upsert failed: ' . $e->getMessage(), previous: $e);
        }
    }

    public function delete(string $id): void
    {
        try {
            $this->client()->post("/collections/{$this->collection}/points/delete", [
                'points' => [$this->toPointId($id)],
            ]);
        } catch (\Throwable $e) {
            throw new VectorStoreException('Qdrant delete failed: ' . $e->getMessage(), previous: $e);
        }
    }

    public function search(array $vector, int $topK, array $filters = []): array
    {
        $body = [
            'vector' => $vector,
            'limit' => $topK,
            'with_payload' => true,
        ];

        if (!empty($filters)) {
            $must = [];
            foreach ($filters as $key => $value) {
                $must[] = ['key' => $key, 'match' => ['value' => $value]];
            }
            $body['filter'] = ['must' => $must];
        }

        try {
            $response = $this->client()->post("/collections/{$this->collection}/points/search", $body);

            if (!$response->successful()) {
                throw new \RuntimeException('HTTP ' . $response->status() . ': ' . $response->body());
            }
        } catch (\Throwable $e) {
            throw new VectorStoreException('Qdrant search failed: ' . $e->getMessage(), previous: $e);
        }

        $results = [];
        foreach ($response->json('result', []) as $point) {
            $payload = $point['payload'] ?? [];
            $toolName = $payload['tool_name'] ?? (string) $point['id'];
            unset($payload['tool_name'], $payload['text'], $payload['hash']);

            $results[] = new VectorSearchResult($toolName, (float) ($point['score'] ?? 0.0), $payload);
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

    /**
     * Deterministically maps an arbitrary tool name to a UUID-formatted
     * string, since Qdrant only accepts unsigned integer or UUID point IDs.
     */
    private function toPointId(string $toolName): string
    {
        $hash = md5($toolName);

        return sprintf(
            '%08s-%04s-%04s-%04s-%12s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12)
        );
    }
}
