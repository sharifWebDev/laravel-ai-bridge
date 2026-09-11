<?php

namespace Sharifuddin\LaravelAiBridge\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Sharifuddin\LaravelAiBridge\Contracts\ToolProviderInterface;

class CachedControllerToolProvider implements ToolProviderInterface
{
    /**
     * Get all available tools from registered controllers.
     *
     * @return array<string, mixed>
     */
    public function getTools(): array
    {
        $cacheEnabled = config('ai-bridge.cache.enabled', true);
        $cacheKey = config('ai-bridge.cache.key', 'ai_controller_tools');
        $ttl = config('ai-bridge.cache.ttl', 3600);

        $fetcher = function () {
            return $this->discoverTools();
        };

        if ($cacheEnabled) {
            return Cache::remember($cacheKey, $ttl, $fetcher);
        }

        return $fetcher();
    }

    /**
     * Discover and map controller tools.
     *
     * @return array<string, mixed>
     */
    public function discoverTools(): array
    {
        $allTools = [];
        $controllers = $this->getRegisteredControllers();

        foreach ($controllers as $controllerClass) {
            if (!class_exists($controllerClass)) {
                continue;
            }

            $reflection = new ReflectionClass($controllerClass);

            // If controller explicitly implements ToolProviderInterface, let it supply its tools
            if ($reflection->implementsInterface(ToolProviderInterface::class)) {
                $instance = app($controllerClass);
                $allTools = array_merge($allTools, $instance->getTools());
                continue;
            }

            // Otherwise reflect on public controller methods
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isStatic() || $method->isAbstract()) {
                    continue;
                }

                $methodName = $method->getName();

                if ($this->isExcludedMethod($methodName)) {
                    continue;
                }

                $parameters = $this->extractMethodParameters($method);
                $resourceName = $this->resourceNameFromController($controllerClass);
                $description = $this->buildMethodDescription($methodName, $resourceName);
                $metadata = $this->buildMethodMetadata($methodName, $resourceName);

                $toolKey = class_basename($controllerClass) . '@' . $methodName;
                $allTools[$toolKey] = [
                    'name' => $methodName,
                    'controller' => $controllerClass,
                    'action' => $methodName,
                    'description' => $description,
                    'permission' => $this->getDefaultPermission($methodName, $controllerClass),
                    'metadata' => $metadata,
                    'parameters' => [
                        'type' => 'OBJECT',
                        'properties' => empty($parameters['properties']) ? new \stdClass() : $parameters['properties'],
                        'required' => $parameters['required'],
                    ],
                ];
            }
        }

        return $allTools;
    }

    /**
     * Retrieve list of controllers from configuration or autodiscovery.
     *
     * @return array<class-string>
     */
    protected function getRegisteredControllers(): array
    {
        $controllers = config('ai-bridge.controllers', []);

        if (!empty($controllers)) {
            return $controllers;
        }

        // Fallback check: Recursively find all controllers inside app/Http/Controllers (up to 4 levels deep)
        if (function_exists('app_path')) {
            $controllerPath = app_path('Http/Controllers');
            if (is_dir($controllerPath)) {
                $discovered = [];

                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($controllerPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );

                // Set max depth to 4 levels deep
                $iterator->setMaxDepth(3);

                foreach ($iterator as $file) {
                    if ($file->isFile() && str_ends_with($file->getFilename(), 'Controller.php')) {
                        // Relative path from app/Http/Controllers
                        $relativePath = str_replace([$controllerPath . DIRECTORY_SEPARATOR, '.php'], '', $file->getPathname());
                        $relativePath = str_replace('/', '\\', $relativePath); // Windows/Linux path normalization

                        $className = 'App\\Http\\Controllers\\' . $relativePath;

                        if (class_exists($className)) {
                            $discovered[] = $className;
                        }
                    }
                }

                return $discovered;
            }
        }

        return [];
    }

    /**
     * Determine if a method is internal / excluded.
     *
     * @param string $name
     * @return bool
     */
    protected function isExcludedMethod(string $name): bool
    {
        $excluded = [
            '__construct',
            '__destruct',
            '__call',
            '__callStatic',
            '__get',
            '__set',
            '__isset',
            '__unset',
            '__sleep',
            '__wakeup',
            '__toString',
            '__invoke',
            'middleware',
            'getMiddleware',
            'callAction',
            'authorize',
            'authorizeForUser',
            'authorizeResource',
            'validateWith',
            'validate',
            'validateWithBag',
            'dispatchNow',
            'dispatchSync',
            'dispatch',
        ];

        return in_array($name, $excluded, true) || str_starts_with($name, '_');
    }

    /**
     * Extract parameter properties from reflection.
     *
     * @param ReflectionMethod $method
     * @return array{properties: array<string, mixed>, required: array<string>}
     */
    protected function extractMethodParameters(ReflectionMethod $method): array
    {
        $properties = [];
        $required = [];

        foreach ($method->getParameters() as $param) {
            $paramName = $param->getName();
            $paramType = 'STRING';

            if ($param->hasType()) {
                $type = $param->getType();
                if ($type instanceof ReflectionNamedType) {
                    $typeName = $type->getName();

                    if ($typeName === Request::class || is_subclass_of($typeName, Request::class)) {
                        continue;
                    }

                    $paramType = match ($typeName) {
                        'int' => 'INTEGER',
                        'bool', 'boolean' => 'BOOLEAN',
                        'float', 'double' => 'NUMBER',
                        'array' => 'ARRAY',
                        default => 'STRING',
                    };
                }
            }

            $properties[$paramName] = [
                'type' => $paramType,
                'description' => "Parameter: {$paramName}",
            ];

            if (!$param->isOptional()) {
                $required[] = $paramName;
            }
        }

        return [
            // যদি প্রপার্টি না থাকে তবে যেন একেবারে খালি অ্যারে বা stdClass যায়, কোনো নেস্টেড অবজেক্ট নয়
            'properties' => empty($properties) ? new \stdClass() : $properties,
            'required' => $required,
        ];
    }
    /**
     * Map common RESTful methods to permissions.
     *
     * @param string $methodName
     * @return string|null
     */
    protected function getDefaultPermission(string $methodName, ?string $controllerClass = null): ?string
    {
        if (!config('ai-bridge.legacy_controllers.enforce_permissions', true)) {
            return null;
        }

        $resource = 'records';

        if ($controllerClass) {
            $controllerName = Str::replaceLast('Controller', '', class_basename($controllerClass));
            $resource = Str::snake(Str::pluralStudly($controllerName));
        }

        $normalized = strtolower($methodName);

        return match (true) {
            in_array($normalized, ['store', 'create', 'bulkstore', 'storeall', 'createall']) => $resource . '.create',
            in_array($normalized, ['update', 'edit', 'bulkupdate', 'updateall', 'togglestatus']) => $resource . '.update',
            in_array($normalized, ['destroy', 'delete', 'remove']) => $resource . '.delete',
            str_contains($normalized, 'report') => $resource . '.report',
            in_array($normalized, ['show', 'view', 'details', 'balance', 'scan', 'get', 'fetch']) => $resource . '.view',
            in_array($normalized, ['index', 'list', 'all', 'indexall', 'listall']) => $resource . '.list',
            default => $resource . '.view',
        };
    }

    protected function resourceNameFromController(string $controllerClass): string
    {
        $controllerName = Str::replaceLast('Controller', '', class_basename($controllerClass));

        return Str::snake(Str::pluralStudly($controllerName));
    }

    protected function buildMethodDescription(string $methodName, string $resourceName): string
    {
        $normalized = strtolower($methodName);
        $label = Str::headline($resourceName);

        return match (true) {
            in_array($normalized, ['index', 'list', 'all', 'indexall', 'listall']) => "List and browse {$label} records, including filtering and pagination.",
            in_array($normalized, ['show', 'view', 'details', 'get', 'fetch']) => "View a single {$label} record and its details.",
            in_array($normalized, ['store', 'create']) => "Create a new {$label} record.",
            in_array($normalized, ['update', 'edit', 'togglestatus']) => "Update or change the status of an existing {$label} record.",
            in_array($normalized, ['destroy', 'delete', 'remove']) => "Delete a {$label} record.",
            str_contains($normalized, 'report') => "Generate a {$label} report or summary.",
            default => "Execute {$methodName} on the {$label} resource.",
        };
    }

    protected function buildMethodMetadata(string $methodName, string $resourceName): array
    {
        $resourceLabel = Str::headline(Str::replace('_', ' ', $resourceName));
        $aliases = [
            $resourceLabel,
            Str::lower($resourceLabel),
            Str::snake($resourceLabel),
            "{$resourceLabel} list",
            "all {$resourceLabel}",
            "{$resourceLabel} records",
        ];

        $normalized = strtolower($methodName);
        if (in_array($normalized, ['index', 'list', 'all', 'indexall', 'listall'], true)) {
            $aliases = array_values(array_unique(array_merge($aliases, [
                Str::lower($resourceLabel) . ' list',
                'list ' . Str::lower($resourceLabel),
                'show ' . Str::lower($resourceLabel),
                'all ' . Str::lower($resourceLabel),
                'view ' . Str::lower($resourceLabel),
            ])));
        }

        return [
            'aliases' => $aliases,
            'entity' => Str::singular($resourceName),
            'action' => $methodName,
        ];
    }
}
