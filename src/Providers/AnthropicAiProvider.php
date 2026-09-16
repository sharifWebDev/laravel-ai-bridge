<?php

namespace Sharifuddin\LaravelAiBridge\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Sharifuddin\LaravelAiBridge\Contracts\AiProviderInterface;
use Sharifuddin\LaravelAiBridge\DTO\AiResponse;
use Sharifuddin\LaravelAiBridge\Exceptions\AiProviderException;

/**
 * Anthropic (Claude) implementation of AiProviderInterface, using the
 * Messages API's native tool-use format. Included as a second example
 * (alongside OpenAiCompatibleProvider) of how a provider whose wire format
 * differs from the canonical declaration shape does its own translation
 * internally, the same way GeminiAiProvider does.
 */
final class AnthropicAiProvider implements AiProviderInterface
{
    private string $apiKey;
    private string $model;
    private string $apiUrl;
    private string $version;

    /** @param array<string, mixed> $config keys: key, model, api_url, version */
    public function __construct(array $config = [])
    {
        $this->apiKey = (string) ($config['key'] ?? '');
        $this->model = (string) ($config['model'] ?? 'claude-sonnet-4-5');
        $this->apiUrl = (string) ($config['api_url'] ?? 'https://api.anthropic.com/v1/messages');
        $this->version = (string) ($config['version'] ?? '2023-06-01');
    }

    public function chat(string $prompt, array $toolDeclarations = [], array $conversationContext = []): AiResponse
    {
        if (empty($this->apiKey)) {
            return AiResponse::error('Anthropic API key is not configured. Set ANTHROPIC_API_KEY or publish config/ai-bridge.php.');
        }

        $messages = [];
        foreach ($conversationContext as $turn) {
            $messages[] = [
                'role' => ($turn['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user',
                'content' => $turn['text'] ?? '',
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $payload = [
            'model' => $this->model,
            'max_tokens' => 1024,
            'messages' => $messages,
        ];

        if (!empty($toolDeclarations)) {
            $payload['tools'] = $this->toAnthropicTools($toolDeclarations);
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->version,
            ])->timeout(30)->post($this->apiUrl, $payload);
        } catch (\Throwable $e) {
            throw new AiProviderException('Exception occurred while calling the Anthropic API: ' . $e->getMessage(), previous: $e);
        }

        if (!$response->successful()) {
            $errorData = $response->json();
            $message = $errorData['error']['message'] ?? ('Anthropic API returned HTTP ' . $response->status());
            Log::warning('[ai-bridge] Anthropic API error: ' . $message);

            return AiResponse::error($message, $errorData ?: [], $response->status());
        }

        $data = $response->json() ?: [];
        $text = null;

        foreach ($data['content'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'tool_use') {
                return AiResponse::toolCall($block['name'] ?? '', $block['input'] ?? [], $data);
            }

            if ($text === null && ($block['type'] ?? null) === 'text') {
                $text = $block['text'] ?? null;
            }
        }

        return AiResponse::text($text ?? 'No response generated.', $data);
    }

    /**
     * Translates the canonical JSON-Schema declarations into Anthropic's
     * tool shape: {name, description, input_schema}.
     *
     * @param array<int, array<string, mixed>> $declarations
     * @return array<int, array<string, mixed>>
     */
    private function toAnthropicTools(array $declarations): array
    {
        return array_map(fn (array $decl) => [
            'name' => $decl['name'],
            'description' => $decl['description'] ?? '',
            'input_schema' => $decl['parameters'] ?? ['type' => 'object', 'properties' => new \stdClass()],
        ], $declarations);
    }
}
