<?php

namespace Sharifuddin\LaravelAiBridge\Providers;

use Illuminate\Support\Facades\Log;
use Sharifuddin\LaravelAiBridge\Contracts\AiProviderInterface;
use Sharifuddin\LaravelAiBridge\DTO\AiResponse;
use Sharifuddin\LaravelAiBridge\Events\AiProviderFailedOver;
use Sharifuddin\LaravelAiBridge\Exceptions\AiProviderException;

/**
 * Tries an ordered list of AiProviderInterface instances in sequence,
 * moving to the next one whenever the current one fails - a rate limit
 * (429), a quota/billing error, a 5xx outage, a network exception, or
 * (per this package's default policy) any other error response at all,
 * since a provider-specific schema quirk can make one provider reject a
 * request another handles fine.
 *
 * The whole chain is invisible to callers: FailoverAiProvider itself
 * implements AiProviderInterface, so AiService and everything else keeps
 * talking to "a single provider" without knowing multiple are configured.
 *
 * Only ever gives up after every provider in the chain has failed, at
 * which point it returns a combined error listing what each one said -
 * never a silent/opaque failure.
 */
final class FailoverAiProvider implements AiProviderInterface
{
    /**
     * @param array<int, array{label: string, provider: AiProviderInterface}> $chain ordered,
     *        tried in sequence starting from index 0 (the configured primary provider)
     */
    public function __construct(private readonly array $chain)
    {
    }

    public function chat(string $prompt, array $toolDeclarations = [], array $conversationContext = []): AiResponse
    {
        $failures = [];

        foreach ($this->chain as $i => $entry) {
            $label = $entry['label'];
            $provider = $entry['provider'];

            try {
                $response = $provider->chat($prompt, $toolDeclarations, $conversationContext);
            } catch (AiProviderException $e) {
                $this->recordFailure($failures, $label, $e->getMessage(), $i);
                continue;
            }

            if (!$response->isError()) {
                return $response;
            }

            $this->recordFailure($failures, $label, (string) $response->text, $i);
        }

        $summary = implode(' | ', array_map(
            fn (array $f) => "{$f['label']}: {$f['reason']}",
            $failures
        ));

        Log::error('[ai-bridge] All configured AI providers failed: ' . $summary);

        return AiResponse::error(
            count($this->chain) > 1
                ? "All {$this->count()} configured AI providers failed: {$summary}"
                : $summary
        );
    }

    public function count(): int
    {
        return count($this->chain);
    }

    /** @param array<int, array{label: string, reason: string}> $failures */
    private function recordFailure(array &$failures, string $label, string $reason, int $index): void
    {
        $next = $this->chain[$index + 1]['label'] ?? null;

        $failures[] = ['label' => $label, 'reason' => $reason];

        Log::warning("[ai-bridge] AI provider [{$label}] failed" . ($next ? ", switching to [{$next}]" : ' (no more providers to try)') . ": {$reason}");

        event(new AiProviderFailedOver($label, $reason, $next));
    }
}
