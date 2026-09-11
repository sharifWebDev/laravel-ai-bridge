<?php

namespace Sharifuddin\LaravelAiBridge\Security;

use Sharifuddin\LaravelAiBridge\Contracts\TenantContextResolverInterface;
use Sharifuddin\LaravelAiBridge\Execution\TenantContext;

/**
 * Default resolver for single-tenant applications: there is no tenant
 * context. Host applications with multi-tenancy should bind their own
 * TenantContextResolverInterface implementation (or CallbackTenantContextResolver)
 * in their AppServiceProvider.
 */
final class NullTenantContextResolver implements TenantContextResolverInterface
{
    public function resolve(): ?TenantContext
    {
        return null;
    }
}
