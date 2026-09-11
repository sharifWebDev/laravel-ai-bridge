<?php

namespace Sharifuddin\LaravelAiBridge\Events;

/**
 * Observability hook: ToolCallFailed. Listen to this via Laravel's Event
 * facade/EventServiceProvider to feed logging, metrics, or tracing
 * without modifying package internals. Never carries secrets - only
 * tool names, scores, latencies and outcome flags.
 */
final class ToolCallFailed
{
    public function __construct(public readonly string $toolName, public readonly string $reason)
    {
    }
}
