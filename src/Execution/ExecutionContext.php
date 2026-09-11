<?php

namespace Sharifuddin\LaravelAiBridge\Execution;

use Illuminate\Support\Facades\Auth;
use Sharifuddin\LaravelAiBridge\Contracts\TenantContextResolverInterface;

/**
 * Carries the "who/where" of a single chat request through the retrieval
 * and execution pipeline: the authenticated user (for permission
 * filtering and authorization re-checks) and the resolved tenant context
 * (for multi-tenant isolation). Never trust anything on this object that
 * originated from AI output.
 */
final class ExecutionContext
{
    /** @param array<string, mixed> $meta */
    public function __construct(
        public readonly mixed $user = null,
        public readonly ?TenantContext $tenant = null,
        public readonly array $meta = [],
    ) {
    }

    public static function fromCurrentRequest(): self
    {
        $user = Auth::hasUser() ? Auth::user() : null;

        $tenant = null;
        if (app()->bound(TenantContextResolverInterface::class)) {
            $tenant = app(TenantContextResolverInterface::class)->resolve();
        }

        return new self($user, $tenant);
    }

    /**
     * Stable, collision-resistant identifier for this context's
     * permission + tenant scope, used to namespace retrieval/response
     * caches so one user/tenant can never be served another's cached
     * results.
     */
    public function securityFingerprint(): string
    {
        $userKey = $this->user && method_exists($this->user, 'getAuthIdentifier')
            ? 'u:' . $this->user->getAuthIdentifier()
            : 'guest';

        $tenantKey = $this->tenant ? 't:' . $this->tenant->fingerprint() : 't:none';

        return $userKey . '|' . $tenantKey;
    }
}
