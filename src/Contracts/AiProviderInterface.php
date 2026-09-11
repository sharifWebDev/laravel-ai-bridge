<?php

namespace Sharifuddin\LaravelAiBridge\Contracts;

use Sharifuddin\LaravelAiBridge\DTO\AiResponse;

/**
 * Chat/generation provider abstraction (Gemini, OpenAI, Anthropic, ...).
 * Implementations receive only the smallest useful set of tool
 * declarations chosen by the retrieval pipeline, never the full registry.
 */
interface AiProviderInterface
{
    /**
     * @param array<int, array<string, mixed>> $toolDeclarations provider-formatted function/tool declarations
     * @param array<int, array<string, string>> $conversationContext compact prior turns, e.g. [['role' => 'user', 'text' => '...']]
     */
    public function chat(string $prompt, array $toolDeclarations = [], array $conversationContext = []): AiResponse;
}
