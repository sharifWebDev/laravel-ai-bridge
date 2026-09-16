<?php

namespace Sharifuddin\LaravelAiBridge\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Sharifuddin\LaravelAiBridge\Contracts\AiProviderInterface;
use Sharifuddin\LaravelAiBridge\DTO\AiResponse;
use Sharifuddin\LaravelAiBridge\Exceptions\AiProviderException;

/**
 * Google Gemini implementation of AiProviderInterface (generateContent).
 * Only ever receives the top-K tool declarations chosen by ToolRetriever,
 * never the full tool catalog, keeping payloads small and token usage low.
 */
final class GeminiAiProvider implements AiProviderInterface
{
    private string $apiKey;
    private string $model;
    private string $apiUrl;

    /** @param array<string, mixed> $config optional explicit config (falls back to ai-bridge.gemini.* / env for backward compatibility) */
    public function __construct(array $config = [])
    {
        $this->apiKey = (string) ($config['key'] ?? config('ai-bridge.gemini.key') ?: env('GEMINI_API_KEY') ?: config('services.gemini.key', ''));
        $this->model = (string) ($config['model'] ?? config('ai-bridge.gemini.model', 'gemini-1.5-flash'));
        $this->apiUrl = (string) ($config['api_url'] ?? config('ai-bridge.gemini.api_url', 'https://generativelanguage.googleapis.com/v1beta/models'));
    }

    public function chat(string $prompt, array $toolDeclarations = [], array $conversationContext = []): AiResponse
    {
        if (empty($this->apiKey)) {
            return AiResponse::error('Gemini API key is not configured. Set GEMINI_API_KEY or publish config/ai-bridge.php.');
        }

        $contents = [];
        foreach ($conversationContext as $turn) {
            $contents[] = [
                'role' => ($turn['role'] ?? 'user') === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $turn['text'] ?? '']],
            ];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $prompt]]];

        $payload = ['contents' => $contents];

        if (!empty($toolDeclarations)) {
            $payload['tools'] = [['functionDeclarations' => $this->toGeminiDeclarations($toolDeclarations)]];
        }

        $endpoint = "{$this->apiUrl}/{$this->model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::timeout(30)->post($endpoint, $payload);
        } catch (\Throwable $e) {
            throw new AiProviderException('Exception occurred while calling the Gemini API: ' . $e->getMessage(), previous: $e);
        }

        if (!$response->successful()) {
            $errorData = $response->json();
            $message = $errorData['error']['message'] ?? ('Gemini API returned HTTP ' . $response->status());
            Log::warning('[ai-bridge] Gemini API error: ' . $message);

            return AiResponse::error($message, $errorData ?: [], $response->status());
        }

        $data = $response->json() ?: [];
        $functionCall = null;
        $text = null;

        foreach ($data['candidates'] ?? [] as $candidate) {
            foreach ($candidate['content']['parts'] ?? [] as $part) {
                if (isset($part['functionCall'])) {
                    $functionCall = $part['functionCall'];
                    break 2;
                }

                if ($text === null && isset($part['text'])) {
                    $text = $part['text'];
                }
            }
        }

        if ($functionCall) {
            return AiResponse::toolCall($functionCall['name'] ?? '', $functionCall['args'] ?? [], $data);
        }

        return AiResponse::text($text ?? 'No response generated.', $data);
    }

    /**
     * Translates the package's canonical, lowercase JSON-Schema tool
     * declarations (ToolDefinition::toDeclaration()) into Gemini's
     * function_declarations shape, which expects uppercase OpenAPI Schema
     * type enums (STRING/INTEGER/NUMBER/BOOLEAN/ARRAY/OBJECT).
     *
     * @param array<int, array<string, mixed>> $declarations
     * @return array<int, array<string, mixed>>
     */
    private function toGeminiDeclarations(array $declarations): array
    {
        return array_map(function (array $decl) {
            $decl['parameters'] = $this->toGeminiSchema(
                $decl['parameters'] ?? ['type' => 'object', 'properties' => new \stdClass(), 'required' => []]
            );

            return $decl;
        }, $declarations);
    }

    /** @param array<string, mixed> $schema */
    private function toGeminiSchema(array $schema): array
    {
        $schema['type'] = strtoupper((string) ($schema['type'] ?? 'object'));

        $properties = $schema['properties'] ?? [];
        if ($properties instanceof \stdClass || empty($properties)) {
            $schema['properties'] = new \stdClass();

            return $schema;
        }

        foreach ($properties as $name => $prop) {
            $properties[$name]['type'] = strtoupper((string) ($prop['type'] ?? 'string'));
            unset($properties[$name]['default']); // Gemini's schema has no "default" keyword

            if (($properties[$name]['type'] ?? null) === 'ARRAY') {
                // Gemini requires 'items' on every ARRAY-type property, or
                // it rejects the ENTIRE request (a 422 covering every tool
                // in the call, not just this one) - default to STRING
                // items if the canonical declaration didn't specify one.
                $properties[$name]['items'] = $this->toGeminiItemsSchema($prop['items'] ?? ['type' => 'string']);
            }
        }

        $schema['properties'] = $properties;

        return $schema;
    }

    /**
     * Maps an 'items' sub-schema for an ARRAY-type property into Gemini's
     * uppercase format. Handles both a plain scalar item type
     * ({type: STRING}) and a nested OBJECT item shape
     * ({type: OBJECT, properties: {...}, required: [...]}) - the latter
     * comes from a bulk/array-of-objects endpoint's wildcard validation
     * rules (e.g. 'items.*.name' => 'required|string'), common on
     * store_all/bulk_store/bulk_update actions.
     *
     * @param array<string, mixed> $items
     * @return array<string, mixed>
     */
    private function toGeminiItemsSchema(array $items): array
    {
        $type = strtoupper((string) ($items['type'] ?? 'string'));
        $mapped = ['type' => $type];

        if ($type !== 'OBJECT') {
            return $mapped;
        }

        $properties = $items['properties'] ?? [];
        if (empty($properties) || !is_array($properties)) {
            $mapped['properties'] = new \stdClass();

            return $mapped;
        }

        foreach ($properties as $propName => $propRule) {
            $properties[$propName]['type'] = strtoupper((string) ($propRule['type'] ?? 'string'));
        }

        $mapped['properties'] = $properties;

        if (!empty($items['required'])) {
            $mapped['required'] = array_values($items['required']);
        }

        return $mapped;
    }
}
