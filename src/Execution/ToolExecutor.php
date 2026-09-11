<?php

namespace Sharifuddin\LaravelAiBridge\Execution;

use Sharifuddin\LaravelAiBridge\Contracts\ToolRegistryInterface;
use Sharifuddin\LaravelAiBridge\DTO\ToolResult;
use Sharifuddin\LaravelAiBridge\Events\ToolCallCompleted;
use Sharifuddin\LaravelAiBridge\Events\ToolCallFailed;
use Sharifuddin\LaravelAiBridge\Events\ToolCallStarted;
use Sharifuddin\LaravelAiBridge\Exceptions\AiBridgeException;
use Sharifuddin\LaravelAiBridge\Exceptions\ToolAuthorizationException;
use Sharifuddin\LaravelAiBridge\Exceptions\ToolExecutionException;
use Sharifuddin\LaravelAiBridge\Exceptions\ToolNotFoundException;
use Sharifuddin\LaravelAiBridge\Security\PermissionFilter;

/**
 * Resolve -> validate -> authorize -> execute -> normalize.
 *
 * This is the single, secure entry point for actually running a tool.
 * Only tools registered in the ToolRegistry can be executed - there is no
 * arbitrary class/method execution from an AI-generated string. Every
 * call re-checks authorization immediately before execution, even though
 * retrieval already filtered by permission: AI output is never trusted as
 * proof of authorization.
 */
final class ToolExecutor
{
    public function __construct(
        private readonly ToolRegistryInterface $registry,
        private readonly ArgumentValidator $validator,
        private readonly PermissionFilter $permissionFilter,
    ) {
    }

    /** @param array<string, mixed> $arguments */
    public function execute(string $toolName, array $arguments, ExecutionContext $context): ToolResult
    {
        event(new ToolCallStarted($toolName, $arguments));
        $start = microtime(true);

        try {
            $tool = $this->registry->get($toolName);
            if (!$tool) {
                throw ToolNotFoundException::named($toolName);
            }

            $validated = $this->validator->validate($tool, $arguments);

            if ($context->user === null) {
                throw ToolAuthorizationException::loginRequired($toolName);
            }

            if (!$this->permissionFilter->allowed($tool, $context)) {
                throw ToolAuthorizationException::forTool($toolName);
            }

            if (!$this->permissionFilter->tenantAllowed($tool, $context)) {
                throw ToolAuthorizationException::missingTenant($toolName);
            }

            if (!$tool->authorize($context->user, $validated, $context)) {
                throw ToolAuthorizationException::forTool($toolName);
            }

            $maxRecords = (int) config('ai-bridge.execution.max_result_records', 50);
            $raw = $tool->execute($validated, $context);
            $result = ToolResult::fromRaw($raw, $maxRecords);

            event(new ToolCallCompleted($toolName, (microtime(true) - $start) * 1000));

            return $result;
        } catch (AiBridgeException $e) {
            event(new ToolCallFailed($toolName, $e->getMessage()));
            throw $e;
        } catch (\Throwable $e) {
            event(new ToolCallFailed($toolName, $e->getMessage()));
            throw new ToolExecutionException("Execution of tool [{$toolName}] failed: " . $e->getMessage(), previous: $e);
        }
    }
}
