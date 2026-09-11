<?php

namespace Sharifuddin\LaravelAiBridge\Events;

/**
 * Observability hook: ToolsRetrieved. Listen to this via Laravel's Event
 * facade/EventServiceProvider to feed logging, metrics, or tracing
 * without modifying package internals. Never carries secrets - only
 * tool names, scores, latencies and outcome flags.
 */
final class ToolsRetrieved
{
    /** @param array<int, array<string, mixed>> $candidates */
    public function __construct(
        public readonly string $normalizedQuery,
        public readonly array $candidates,
        public readonly bool $isAmbiguous,
        public readonly bool $isLowConfidence,
    ) {
    }
}
