<?php

namespace Sharifuddin\LaravelAiBridge\DTO;

/**
 * Normalized response from an AiProviderInterface, hiding each provider's
 * wire format (Gemini candidates/parts, OpenAI choices/tool_calls, ...)
 * behind a single shape the rest of the package can rely on.
 */
final class AiResponse
{
    /** @param array<string, mixed> $toolArguments */
    private function __construct(
        public readonly string $type,
        public readonly ?string $text,
        public readonly ?string $toolName,
        public readonly array $toolArguments,
        public readonly array $raw,
    ) {
    }

    public static function text(string $text, array $raw = []): self
    {
        return new self('text', $text, null, [], $raw);
    }

    /** @param array<string, mixed> $arguments */
    public static function toolCall(string $toolName, array $arguments, array $raw = []): self
    {
        return new self('tool_call', null, $toolName, $arguments, $raw);
    }

    public static function error(string $message, array $raw = []): self
    {
        return new self('error', $message, null, [], $raw);
    }

    public function isToolCall(): bool
    {
        return $this->type === 'tool_call';
    }

    public function isError(): bool
    {
        return $this->type === 'error';
    }
}
