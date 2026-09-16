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
        public readonly ?int $statusCode = null,
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

    /**
     * @param int|null $statusCode the HTTP status returned by the provider, when known -
     *        used by FailoverAiProvider to decide whether trying the next configured
     *        provider is worthwhile (e.g. a 429 rate limit is; a malformed-request 400
     *        caused by our own schema would just fail identically everywhere, though
     *        by default FailoverAiProvider still moves on regardless, per the "switch
     *        on ANY provider error" behavior this package defaults to).
     */
    public static function error(string $message, array $raw = [], ?int $statusCode = null): self
    {
        return new self('error', $message, null, [], $raw, $statusCode);
    }

    public function isToolCall(): bool
    {
        return $this->type === 'tool_call';
    }

    public function isError(): bool
    {
        return $this->type === 'error';
    }

    /**
     * True when the failure looks transient/provider-side (rate limit,
     * quota, overloaded, or any 5xx) rather than a client-side mistake -
     * informational only; FailoverAiProvider's default policy is to move
     * to the next provider on ANY error, not just retryable ones.
     */
    public function isRetryable(): bool
    {
        if ($this->statusCode === 429 || ($this->statusCode !== null && $this->statusCode >= 500)) {
            return true;
        }

        $needle = strtolower((string) $this->text);

        foreach (['rate limit', 'quota', 'overloaded', 'resource_exhausted', 'too many requests', 'unavailable'] as $marker) {
            if (str_contains($needle, $marker)) {
                return true;
            }
        }

        return false;
    }
}
