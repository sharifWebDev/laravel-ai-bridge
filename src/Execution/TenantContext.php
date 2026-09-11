<?php

namespace Sharifuddin\LaravelAiBridge\Execution;

/**
 * Generic tenant/company/branch context. The package treats this as an
 * opaque bag of attributes; the host application decides what keys matter
 * (company_id, branch_id, organization_id, ...) and how its Actions use
 * them to scope queries.
 */
final class TenantContext
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private array $attributes = [])
    {
    }

    /** @param array<string, mixed> $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self($attributes);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Short deterministic identifier safe to embed in cache keys, so
     * retrieval/embedding caches never leak results across tenants.
     */
    public function fingerprint(): string
    {
        ksort($this->attributes);

        return substr(sha1(json_encode($this->attributes) ?: ''), 0, 16);
    }
}
