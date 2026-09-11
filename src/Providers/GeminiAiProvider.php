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

    public function __construct()
    {
        $this->apiKey = (string) (config('ai-bridge.gemini.key') ?: env('GEMINI_API_KEY') ?: config('services.gemini.key', ''));
        $this->model = (string) config('ai-bridge.gemini.model', 'gemini-1.5-flash');
        $this->apiUrl = (string) config('ai-bridge.gemini.api_url', 'https://generativelanguage.googleapis.com/v1beta/models');
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
            $payload['tools'] = [['functionDeclarations' => $toolDeclarations]];
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

            return AiResponse::error($message, $errorData ?: []);
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
}
