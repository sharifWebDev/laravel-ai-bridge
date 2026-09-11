<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Unit;

use Sharifuddin\LaravelAiBridge\Contracts\ToolProviderInterface;
use Sharifuddin\LaravelAiBridge\Contracts\ToolRegistryInterface;
use Sharifuddin\LaravelAiBridge\Registry\ToolRegistry;
use Sharifuddin\LaravelAiBridge\Tests\TestCase;

class LegacyReportController implements ToolProviderInterface
{
    public function getTools(): array
    {
        return [
            'exportReport' => [
                'name' => 'exportReport',
                'controller' => self::class,
                'action' => 'exportReport',
                'description' => 'Export a monthly analytical report',
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

    public function exportReport(string $month = 'january'): array
    {
        return ['month' => $month, 'exported' => true];
    }
}

class BackwardCompatibilityTest extends TestCase
{
    public function test_legacy_controller_tools_are_absorbed_into_the_new_registry(): void
    {
        $registry = new ToolRegistry();
        $registry->mergeLegacyProvider(new LegacyReportController());

        $this->assertTrue($registry->has('legacy_report_controller_export_report'));
    }

    public function test_legacy_tool_definition_still_executes_the_original_controller_method(): void
    {
        $registry = new ToolRegistry();
        $registry->mergeLegacyProvider(new LegacyReportController());

        $tool = $registry->get('legacy_report_controller_export_report');
        $this->assertNotNull($tool);

        $result = $tool->execute(['month' => 'march'], new \Sharifuddin\LaravelAiBridge\Execution\ExecutionContext());

        $this->assertSame(['month' => 'march', 'exported' => true], $result);
    }

    public function test_legacy_ai_processor_interface_methods_are_unchanged(): void
    {
        // The original processPrompt()/selectRelevantTools() behavior must
        // remain callable exactly as before for any code depending on
        // AiProcessorInterface directly, even though AiController now
        // uses the new chat() pipeline instead.
        $service = $this->app->make(\Sharifuddin\LaravelAiBridge\Contracts\AiProcessorInterface::class);

        $this->assertTrue(method_exists($service, 'processPrompt'));
        $this->assertTrue(method_exists($service, 'selectRelevantTools'));
    }

    public function test_service_provider_still_auto_registers_legacy_controllers_when_enabled(): void
    {
        config([
            'ai-bridge.legacy_controllers.enabled' => true,
            'ai-bridge.controllers' => [LegacyReportController::class],
        ]);

        // Force a fresh registry resolution with the new config.
        $this->app->forgetInstance(ToolRegistryInterface::class);
        $registry = $this->app->make(ToolRegistryInterface::class);

        $this->assertTrue($registry->has('legacy_report_controller_export_report'));
    }

    public function test_legacy_controller_permissions_match_app_route_permissions(): void
    {
        config([
            'ai-bridge.controllers' => [\App\Http\Controllers\Api\UserController::class],
        ]);

        $provider = new \Sharifuddin\LaravelAiBridge\Services\CachedControllerToolProvider();
        $tools = $provider->discoverTools();

        $this->assertSame('users.list', $tools['UserController@index']['permission'] ?? null);
        $this->assertSame('users.view', $tools['UserController@show']['permission'] ?? null);
        $this->assertSame('users.create', $tools['UserController@store']['permission'] ?? null);
        $this->assertSame('users.update', $tools['UserController@update']['permission'] ?? null);
        $this->assertSame('users.delete', $tools['UserController@destroy']['permission'] ?? null);
    }
}
