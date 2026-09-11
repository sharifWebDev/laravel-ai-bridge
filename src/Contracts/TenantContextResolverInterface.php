<?php

namespace Sharifuddin\LaravelAiBridge\Contracts;

use Sharifuddin\LaravelAiBridge\Execution\TenantContext;

/**
 * Host applications implement/bind this to teach the package how to
 * resolve the "current" tenant/company/branch, without the package ever
 * assuming a specific multi-tenancy package or database column.
 */
interface TenantContextResolverInterface
{
    public function resolve(): ?TenantContext;
}
