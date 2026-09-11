<?php

namespace Sharifuddin\LaravelAiBridge\Registry;

use Sharifuddin\LaravelAiBridge\Contracts\ToolInterface;
use Sharifuddin\LaravelAiBridge\Contracts\ToolProviderInterface as LegacyToolProviderInterface;
use Sharifuddin\LaravelAiBridge\Contracts\ToolRegistryInterface;
use Sharifuddin\LaravelAiBridge\Tools\LegacyControllerToolAdapter;

/**
 * Centralized, in-memory registry of first-class AI tools for the current
 * request/process. Tools are registered from config('ai-bridge.tools'),
 * from AI::tool()/register() calls, and - for backward compatibility -
 * from any bound legacy ToolProviderInterface via mergeLegacyProvider().
 */
final class ToolRegistry implements ToolRegistryInterface
{
    /** @var array<string, ToolInterface> */
    private array $tools = [];

    public function register(ToolInterface|string $tool): void
    {
        $instance = is_string($tool) ? app($tool) : $tool;

        if (!$instance instanceof ToolInterface) {
            throw new \InvalidArgumentException('Registered tool must implement ' . ToolInterface::class);
        }

        $this->tools[$instance->name()] = $instance;
    }

    public function unregister(string $name): void
    {
        unset($this->tools[$name]);
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    public function get(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    /** @return array<string, ToolInterface> */
    public function all(): array
    {
        return $this->tools;
    }

    /**
     * Simple name/description substring search - used by `ai:tools:list`
     * and developer tooling, not by the semantic retrieval pipeline.
     *
     * @return array<string, ToolInterface>
     */
    public function search(string $needle): array
    {
        $needle = mb_strtolower($needle);

        return array_filter($this->tools, function (ToolInterface $tool) use ($needle) {
            return str_contains(mb_strtolower($tool->name()), $needle)
                || str_contains(mb_strtolower($tool->description()), $needle);
        });
    }

    public function version(): string
    {
        $signature = [];
        foreach ($this->tools as $name => $tool) {
            $signature[] = $name . '@' . substr(sha1($tool->embeddingText()), 0, 8);
        }
        sort($signature);

        return substr(sha1(implode(',', $signature)), 0, 16);
    }

    /**
     * Absorbs tools discovered by a legacy array-based ToolProviderInterface
     * (e.g. CachedControllerToolProvider reflecting over app controllers)
     * as LegacyControllerToolAdapter instances, so old and new tools are
     * retrieved and executed through the exact same pipeline.
     */
    public function mergeLegacyProvider(LegacyToolProviderInterface $provider): void
    {
        foreach ($provider->getTools() as $toolKey => $definition) {
            $adapter = new LegacyControllerToolAdapter((string) $toolKey, $definition);

            // Never let a legacy controller tool silently overwrite a
            // first-class tool that was explicitly registered under the
            // same name.
            if (!$this->has($adapter->name())) {
                $this->tools[$adapter->name()] = $adapter;
            }
        }
    }
}
