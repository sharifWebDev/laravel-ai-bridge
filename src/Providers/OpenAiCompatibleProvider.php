<?php

namespace Sharifuddin\LaravelAiBridge\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Sharifuddin\LaravelAiBridge\Contracts\AiProviderInterface;
use Sharifuddin\LaravelAiBridge\DTO\AiResponse;
use Sharifuddin\LaravelAiBridge\Exceptions\AiProviderException;

/**
 * Drives any provider that implements the OpenAI-style
 * `POST {base_url}/chat/completions` wire format with function/tool
 * calling - this covers OpenAI (ChatGPT), DeepSeek, Groq, Mistral,
 * OpenRouter, Together AI, local runtimes exposing an OpenAI-compatible
 * endpoint (Ollama, vLLM, LM Studio, ...), and effectively any "custom AI
 * API" the user wants to plug in, since this shape has become the de
 * facto standard for LLM chat/function-calling APIs.
 *
 * A single class backs all of them - only the config (base_url, key,
 * model, extra headers) differs, which is exactly what makes adding a new
 * provider a config-only change (see config/ai-bridge.php `providers`).
 */
final class OpenAiCompatibleProvider implements AiProviderInterface
{
    private string $label;
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    /** @var array<string, string> */
    private array $extraHeaders;

    private bool $strictAuthHeader;

    /**
     * @param array<string, mixed> $config keys: key, model, base_url, headers (optional), auth_header (optional, default "Authorization: Bearer {key}")
     */
    public function __construct(array $config, string $label = 'openai-compatible')
    {
        $this->label = $label;
        $this->apiKey = (string) ($config['key'] ?? '');
        $this->model = (string) ($config['model'] ?? '');
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        $this->extraHeaders = (array) ($config['headers'] ?? []);
        $this->strictAuthHeader = (bool) ($config['use_bearer_auth'] ?? true);
    }

    public function chat(string $prompt, array $toolDeclarations = [], array $conversationContext = []): AiResponse
    {
        if (empty($this->apiKey)) {
            return AiResponse::error("[{$this->label}] API key is not configured. Set it in config/ai-bridge.php (providers.{$this->label}.key) or the matching env variable.");
        }

        if (empty($this->baseUrl)) {
            return AiResponse::error("[{$this->label}] base_url is not configured.");
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
            'messages' => $messages,
        ];

        if (!empty($toolDeclarations)) {
            // Canonical declarations from ToolDefinition::toDeclaration() are
            // already lowercase JSON-Schema - the exact shape OpenAI-style
            // APIs expect, so no translation is needed here (unlike Gemini).
            $payload['tools'] = array_map(fn (array $decl) => [
                'type' => 'function',
                'function' => $decl,
            ], $toolDeclarations);
            $payload['tool_choice'] = 'auto';
        }

        $headers = $this->extraHeaders;
        if ($this->strictAuthHeader) {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post("{$this->baseUrl}/chat/completions", $payload);
        } catch (\Throwable $e) {
            throw new AiProviderException("Exception occurred while calling the [{$this->label}] API: " . $e->getMessage(), previous: $e);
        }

        if (!$response->successful()) {
            $errorData = $response->json();
            $message = $errorData['error']['message'] ?? ("[{$this->label}] API returned HTTP " . $response->status());
            Log::warning("[ai-bridge] {$this->label} API error: " . $message);

            return AiResponse::error($message, $errorData ?: [], $response->status());
        }

        $data = $response->json() ?: [];
        $message = $data['choices'][0]['message'] ?? [];

        $toolCall = $message['tool_calls'][0] ?? null;
        if ($toolCall) {
            $name = $toolCall['function']['name'] ?? '';
            $rawArguments = $toolCall['function']['arguments'] ?? '{}';
            $arguments = is_array($rawArguments) ? $rawArguments : (json_decode((string) $rawArguments, true) ?: []);

            return AiResponse::toolCall($name, $arguments, $data);
        }

        return AiResponse::text($message['content'] ?? 'No response generated.', $data);
    }
}
