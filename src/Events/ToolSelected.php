<?php

namespace Sharifuddin\LaravelAiBridge\Events;

/**
 * Observability hook: ToolSelected. Listen to this via Laravel's Event
 * facade/EventServiceProvider to feed logging, metrics, or tracing
 * without modifying package internals. Never carries secrets - only
 * tool names, scores, latencies and outcome flags.
 */
final class ToolSelected
{
    /** @param array<string, mixed> $arguments */
    public function __construct(public readonly string $toolName, public readonly array $arguments)
    {
    }
}
