<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Unit;

use Sharifuddin\LaravelAiBridge\Contracts\ToolRegistryInterface;
use Sharifuddin\LaravelAiBridge\Tests\Fakes\GetOrderTool;
use Sharifuddin\LaravelAiBridge\Tests\Fakes\ListUsersTool;
use Sharifuddin\LaravelAiBridge\Tests\TestCase;

class ToolRegistryTest extends TestCase
{
    public function test_registers_and_retrieves_tools(): void
    {
        $registry = $this->app->make(ToolRegistryInterface::class);
        $registry->register(new ListUsersTool());

        $this->assertTrue($registry->has('list_users'));
        $this->assertSame('list_users', $registry->get('list_users')->name());
        $this->assertNull($registry->get('unknown_tool'));
    }

    public function test_can_register_by_class_string(): void
    {
        $registry = $this->app->make(ToolRegistryInterface::class);
        $registry->register(GetOrderTool::class);

        $this->assertTrue($registry->has('get_order'));
    }

    public function test_unregister_removes_tool(): void
    {
        $registry = $this->app->make(ToolRegistryInterface::class);
        $registry->register(new ListUsersTool());
        $registry->unregister('list_users');

        $this->assertFalse($registry->has('list_users'));
    }

    public function test_version_changes_when_registry_contents_change(): void
    {
        $registry = $this->app->make(ToolRegistryInterface::class);
        $before = $registry->version();

        $registry->register(new ListUsersTool());
        $after = $registry->version();

        $this->assertNotSame($before, $after);
    }

    public function test_search_matches_name_and_description(): void
    {
        $registry = $this->app->make(ToolRegistryInterface::class);
        $registry->register(new ListUsersTool());
        $registry->register(new GetOrderTool());

        $results = $registry->search('invoice');

        $this->assertArrayHasKey('get_order', $results);
        $this->assertArrayNotHasKey('list_users', $results);
    }
}
