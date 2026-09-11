<?php

namespace Sharifuddin\LaravelAiBridge\Contracts;

interface ToolRegistryInterface
{
    /**
     * Register a tool instance or a class-string resolvable via the
     * container.
     *
     * @param ToolInterface|class-string<ToolInterface> $tool
     */
    public function register(ToolInterface|string $tool): void;

    public function unregister(string $name): void;

    public function has(string $name): bool;

    public function get(string $name): ?ToolInterface;

    /** @return array<string, ToolInterface> */
    public function all(): array;

    /**
     * Cheap fingerprint of the current registry contents (names + a short
     * hash of each tool's embedding text). Used to safely namespace
     * retrieval caches so a tool change invalidates stale cache entries.
     */
    public function version(): string;
}
