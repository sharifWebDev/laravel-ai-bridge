<?php

namespace Sharifuddin\LaravelAiBridge\Contracts;

use Sharifuddin\LaravelAiBridge\DTO\AiResponse;

/**
 * Chat/generation provider abstraction (Gemini, OpenAI, DeepSeek,
 * Anthropic, any custom OpenAI-compatible API, ...). Implementations
 * receive only the smallest useful set of tool declarations chosen by the
 * retrieval pipeline, never the full registry.
 *
 * Switching providers is a config-only change (ai-bridge.provider / the
 * AI_BRIDGE_PROVIDER env var) resolved by AiProviderManager - callers of
 * this interface never need to know which concrete provider is active.
 */
interface AiProviderInterface
{
    /**
     * @param array<int, array<string, mixed>> $toolDeclarations canonical, provider-agnostic JSON-Schema-style
     *        function declarations, as produced by ToolDefinition::toDeclaration() - i.e.
     *        [['name' => ..., 'description' => ..., 'parameters' => ['type' => 'object', 'properties' => [...], 'required' => [...]]]].
     *        Implementations that need a different wire format (e.g. Gemini's uppercase
     *        type enums) MUST translate this canonical shape internally rather than
     *        assuming callers already speak their dialect.
     * @param array<int, array<string, string>> $conversationContext compact prior turns, e.g. [['role' => 'user', 'text' => '...']]
     */
    public function chat(string $prompt, array $toolDeclarations = [], array $conversationContext = []): AiResponse;
}
