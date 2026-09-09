<?php

namespace App\Services;

use App\Contracts\ToolProviderInterface;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use ReflectionMethod;

class CachedControllerToolProvider implements ToolProviderInterface
{
    public function getTools(): array
    {
        return Cache::remember('ai_controller_tools', 3600, function () {
            $allTools = [];
            $controllers = [
                \App\Http\Controllers\UserController::class,
                \App\Http\Controllers\InventoryController::class,
            ];

            foreach ($controllers as $controllerClass) {
                if (!class_exists($controllerClass)) {
                    continue;
                }

                $reflection = new ReflectionClass($controllerClass);

                if ($reflection->implementsInterface(ToolProviderInterface::class)) {
                    $instance = app($controllerClass);
                    $allTools = array_merge($allTools, $instance->getTools());
                    continue;
                }

                foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    $methodName = $method->getName();

                    if ($this->isExcludedMethod($methodName)) {
                        continue;
                    }

                    $allTools[$methodName] = [
                        "name" => $methodName,
                        "description" => "Execute {$methodName} on " . class_basename($controllerClass),
                        "permission" => $this->getDefaultPermission($methodName),
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => ["id" => ["type" => "STRING", "description" => "Record ID"]]
                        ]
                    ];
                }
            }

            return $allTools;
        });
    }

    protected function isExcludedMethod(string $name): bool
    {
        return in_array($name, ['__construct', 'middleware', 'getMiddleware', 'callAction']) || str_starts_with($name, '_');
    }

    protected function getDefaultPermission(string $methodName): ?string
    {
        return match($methodName) {
            'store', 'create' => 'create-records',
            'update', 'edit' => 'update-records',
            'destroy' => 'delete-records',
            default => 'view-records',
        };
    }
}
