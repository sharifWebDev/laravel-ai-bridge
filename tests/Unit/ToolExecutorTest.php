<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Unit;

use Sharifuddin\LaravelAiBridge\Exceptions\ToolAuthorizationException;
use Sharifuddin\LaravelAiBridge\Exceptions\ToolNotFoundException;
use Sharifuddin\LaravelAiBridge\Exceptions\ToolValidationException;
use Sharifuddin\LaravelAiBridge\Execution\ExecutionContext;
use Sharifuddin\LaravelAiBridge\Execution\TenantContext;
use Sharifuddin\LaravelAiBridge\Execution\ToolExecutor;
use Sharifuddin\LaravelAiBridge\Contracts\ToolRegistryInterface;
use Sharifuddin\LaravelAiBridge\Tests\Fakes\CreateCustomerTool;
use Sharifuddin\LaravelAiBridge\Tests\Fakes\GetOrderTool;
use Sharifuddin\LaravelAiBridge\Tests\Fakes\ListUsersTool;
use Sharifuddin\LaravelAiBridge\Tests\TestCase;

class ToolExecutorTest extends TestCase
{
    private function fakeUser(array $abilities = []): object
    {
        return new class ($abilities) {
            public function __construct(private array $abilities)
            {
            }

            public function getAuthIdentifier(): int
            {
                return 1;
            }

            public function can($ability): bool
            {
                return in_array($ability, $this->abilities, true);
            }
        };
    }

    public function test_throws_for_unregistered_tool(): void
    {
        $this->expectException(ToolNotFoundException::class);

        $executor = $this->app->make(ToolExecutor::class);
        $executor->execute('does_not_exist', [], new ExecutionContext());
    }

    public function test_re_checks_authorization_even_though_ai_selected_the_tool(): void
    {
        $registry = $this->app->make(ToolRegistryInterface::class);
        $registry->register(new ListUsersTool()); // requires 'view-users'

        $executor = $this->app->make(ToolExecutor::class);

        $this->expectException(ToolAuthorizationException::class);
        $executor->execute('list_users', [], new ExecutionContext($this->fakeUser([]))); // no permission
    }

    public function test_executes_successfully_when_authorized(): void
    {
        $registry = $this->app->make(ToolRegistryInterface::class);
        $registry->register(new ListUsersTool());

        $executor = $this->app->make(ToolExecutor::class);
        $result = $executor->execute('list_users', [], new ExecutionContext($this->fakeUser(['view-users'])));

        $this->assertTrue($result->success);
    }

    public function test_rejects_invalid_arguments_before_execution(): void
    {
        $registry = $this->app->make(ToolRegistryInterface::class);
        $registry->register(new GetOrderTool());

        $executor = $this->app->make(ToolExecutor::class);

        $this->expectException(ToolValidationException::class);
        $executor->execute('get_order', [], new ExecutionContext()); // missing required invoice_number
    }

    public function test_denies_tenant_scoped_tool_without_tenant_context(): void
    {
        $registry = $this->app->make(ToolRegistryInterface::class);
        $registry->register(new CreateCustomerTool());

        $executor = $this->app->make(ToolExecutor::class);
        $user = $this->fakeUser(['create-customers']);

        $this->expectException(ToolAuthorizationException::class);
        $executor->execute('create_customer', ['name' => 'Acme'], new ExecutionContext($user));
    }

    public function test_executes_tenant_scoped_tool_with_tenant_context(): void
    {
        $registry = $this->app->make(ToolRegistryInterface::class);
        $registry->register(new CreateCustomerTool());

        $executor = $this->app->make(ToolExecutor::class);
        $user = $this->fakeUser(['create-customers']);
        $context = new ExecutionContext($user, TenantContext::fromArray(['company_id' => 9]));

        $result = $executor->execute('create_customer', ['name' => 'Acme'], $context);

        $this->assertTrue($result->success);
        $this->assertSame(9, $result->data[0]['tenant']['company_id']);
    }

    public function test_truncates_large_result_sets_and_reports_totals(): void
    {
        $registry = $this->app->make(ToolRegistryInterface::class);
        $registry->register(new class extends ListUsersTool {
            public function permission(): ?string
            {
                return null;
            }

            public function execute(array $arguments, \Sharifuddin\LaravelAiBridge\Execution\ExecutionContext $context): mixed
            {
                return range(1, 200); // simulate 200 raw rows
            }
        });

        config(['ai-bridge.execution.max_result_records' => 10]);

        $executor = $this->app->make(ToolExecutor::class);
        $result = $executor->execute('list_users', [], new ExecutionContext());

        $this->assertCount(10, $result->data);
        $this->assertSame(200, $result->meta['total']);
        $this->assertTrue($result->meta['truncated']);
    }
}
