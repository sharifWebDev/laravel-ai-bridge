<?php

namespace Sharifuddin\LaravelAiBridge\Events;

/**
 * Observability hook: EmbeddingGenerated. Listen to this via Laravel's Event
 * facade/EventServiceProvider to feed logging, metrics, or tracing
 * without modifying package internals. Never carries secrets - only
 * tool names, scores, latencies and outcome flags.
 */
final class EmbeddingGenerated
{
    public function __construct(public readonly string $provider, public readonly int $textLength, public readonly float $durationMs)
    {
    }
}
