<?php

namespace Sharifuddin\LaravelAiBridge\Events;

/**
 * Observability hook: fired by FailoverAiProvider every time a configured
 * provider fails (rate limit, quota, outage, bad config, ...) and the
 * chain moves on to the next one. Listen to this via Laravel's Event
 * facade/EventServiceProvider to feed logging, alerting, or metrics
 * dashboards without modifying package internals. Never carries secrets -
 * only the provider label and a human-readable failure reason.
 */
final class AiProviderFailedOver
{
    public function __construct(
        public readonly string $failedProvider,
        public readonly string $reason,
        public readonly ?string $nextProvider,
    ) {
    }
}
