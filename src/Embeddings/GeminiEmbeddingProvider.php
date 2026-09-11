<?php

namespace Sharifuddin\LaravelAiBridge\Embeddings;

use Illuminate\Support\Facades\Http;
use Sharifuddin\LaravelAiBridge\Contracts\EmbeddingProviderInterface;
use Sharifuddin\LaravelAiBridge\Exceptions\EmbeddingException;

/**
 * Google Gemini text embedding provider (models/text-embedding-004 by
 * default). Requires GEMINI_API_KEY (or ai-bridge.gemini.key) to be
 * configured; throws EmbeddingException otherwise so callers (ToolRetriever)
 * can fall back to lexical-only retrieval instead of hard-failing.
 */
final class GeminiEmbeddingProvider implements EmbeddingProviderInterface
{
    private string $apiKey;
    private string $model;
    private string $apiUrl;
    private int $dims;

    public function __construct()
    {
        $this->apiKey = (string) (config('ai-bridge.gemini.key') ?: env('GEMINI_API_KEY') ?: '');
        $this->model = (string) config('ai-bridge.embedding.gemini_model', 'gemini-embedding-001');
        $this->apiUrl = (string) config('ai-bridge.gemini.api_url', 'https://generativelanguage.googleapis.com/v1beta/models');
        $this->dims = (int) config('ai-bridge.embedding.dimensions', 768);
    }

    public function embed(string $text): array
    {
        if (empty($this->apiKey)) {
            throw new EmbeddingException('Gemini API key is not configured; cannot generate embeddings.');
        }

        $endpoint = "{$this->apiUrl}/{$this->model}:embedContent?key={$this->apiKey}";

        $payload = [
            'model' => "models/{$this->model}",
            'content' => ['parts' => [['text' => $text]]],
        ];

        if ($this->dims > 0) {
            $payload['outputDimensionality'] = $this->dims;
        }

        try {
            $response = Http::timeout(15)->post($endpoint, $payload);
        } catch (\Throwable $e) {
            throw new EmbeddingException('Failed to reach Gemini embedding API: ' . $e->getMessage(), previous: $e);
        }

        if (!$response->successful()) {
            $errorData = $response->json();
            $message = $errorData['error']['message'] ?? ('HTTP ' . $response->status());
            throw new EmbeddingException('Gemini embedding API returned ' . $message);
        }

        $values = $response->json('embedding.values');

        if (!is_array($values) || empty($values)) {
            throw new EmbeddingException('Gemini embedding API returned an empty vector.');
        }

        return array_map('floatval', $values);
    }

    public function identifier(): string
    {
        return "gemini:{$this->model}";
    }

    public function dimensions(): int
    {
        return $this->dims;
    }
}
