<?php

namespace Sharifuddin\LaravelAiBridge\Security;

use Sharifuddin\LaravelAiBridge\Contracts\TenantContextResolverInterface;
use Sharifuddin\LaravelAiBridge\Execution\TenantContext;

/**
 * Convenience resolver for host applications: bind it with a closure that
 * returns the current tenant attributes (or null), without needing to
 * write a dedicated class.
 *
 *   $this->app->bind(TenantContextResolverInterface::class, fn () =>
 *       new CallbackTenantContextResolver(fn () => auth()->user()
 *           ? ['company_id' => auth()->user()->company_id]
 *           : null
 *       )
 *   );
 */
final class CallbackTenantContextResolver implements TenantContextResolverInterface
{
    /** @param \Closure(): (array<string, mixed>|null) $callback */
    public function __construct(private readonly \Closure $callback)
    {
    }

    public function resolve(): ?TenantContext
    {
        $attributes = ($this->callback)();

        return $attributes === null ? null : TenantContext::fromArray($attributes);
    }
}
