<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Unit;

use Sharifuddin\LaravelAiBridge\Contracts\ToolProviderInterface;
use Sharifuddin\LaravelAiBridge\Services\CachedControllerToolProvider;
use Sharifuddin\LaravelAiBridge\Tests\TestCase;

class DummyCustomController implements ToolProviderInterface
{
    public function getTools(): array
    {
        return [
            'exportReport' => [
                'name' => 'exportReport',
                'description' => 'Export monthly analytical report',
                'permission' => 'view-reports',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'month' => ['type' => 'STRING', 'description' => 'Target month'],
                    ],
                ],
            ],
        ];
    }
}

class DummyReflectedController
{
    public function calculateTax(int $amount, string $country = 'US'): float
    {
        return 0.0;
    }

    protected function internalHelper(): void
    {
    }
}

class ToolProviderTest extends TestCase
{
    public function test_discovers_custom_tools_from_interface(): void
    {
        config(['ai-bridge.controllers' => [DummyCustomController::class]]);

        $provider = new CachedControllerToolProvider();
        $tools = $provider->getTools();

        $this->assertArrayHasKey('exportReport', $tools);
        $this->assertSame('exportReport', $tools['exportReport']['name']);
        $this->assertSame('view-reports', $tools['exportReport']['permission']);
    }

    public function test_reflects_public_methods_and_parameters(): void
    {
        config(['ai-bridge.controllers' => [DummyReflectedController::class]]);

        $provider = new CachedControllerToolProvider();
        $tools = $provider->getTools();

        $toolKey = 'DummyReflectedController@calculateTax';
        $this->assertArrayHasKey($toolKey, $tools);
        $this->assertSame('calculateTax', $tools[$toolKey]['name']);
        $this->assertArrayHasKey('amount', $tools[$toolKey]['parameters']['properties']);
        $this->assertSame('INTEGER', $tools[$toolKey]['parameters']['properties']['amount']['type']);
        $this->assertContains('amount', $tools[$toolKey]['parameters']['required']);
    }
}
