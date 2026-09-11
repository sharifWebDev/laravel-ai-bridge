<?php

namespace Sharifuddin\LaravelAiBridge\Security;

use Sharifuddin\LaravelAiBridge\Contracts\ToolInterface;
use Sharifuddin\LaravelAiBridge\Execution\ExecutionContext;

/**
 * Enforces Laravel authorization and tenant isolation at both retrieval
 * time (so disallowed tools are never even shown to the AI) and,
 * critically, again at execution time via ToolExecutor - the AI's tool
 * choice is never trusted as proof of authorization.
 */
final class PermissionFilter
{
    public function allowed(ToolInterface $tool, ExecutionContext $context): bool
    {
        $permission = $tool->permission();

        if ($permission === null) {
            return true;
        }

        $user = $context->user;

        // A permission is required but no authenticated user is present:
        // deny by default rather than assuming public access.
        if ($user === null) {
            return false;
        }

        if (!method_exists($user, 'can')) {
            return true;
        }

        return (bool) $user->can($permission);
    }

    public function tenantAllowed(ToolInterface $tool, ExecutionContext $context): bool
    {
        if (!$tool->requiresTenant()) {
            return true;
        }

        return $context->tenant !== null;
    }
}
