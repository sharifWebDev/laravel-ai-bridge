<?php

namespace Sharifuddin\LaravelAiBridge\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Sharifuddin\LaravelAiBridge\Contracts\ToolProviderInterface;

class CachedControllerToolProvider implements ToolProviderInterface
{
    private ControllerRequestParameterAnalyzer $parameterAnalyzer;

    public function __construct(?ControllerRequestParameterAnalyzer $parameterAnalyzer = null)
    {
        $this->parameterAnalyzer = $parameterAnalyzer ?? new ControllerRequestParameterAnalyzer();
    }

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

                $resourceName = $this->resourceNameFromController($controllerClass);
                $parameters = $this->extractMethodParameters($method, $resourceName);
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
     * Combines several sources so the AI actually sees every knob the
     * endpoint supports, not just its typed signature:
     *
     *  1) the method's typed scalar parameters (as before) - including
     *     route-model-binding parameters (e.g. `show(User $user)`), which
     *     are exposed to the AI as a plain record identifier since that's
     *     all it ever needs to supply (see LegacyControllerToolAdapter::
     *     execute(), which resolves the actual model instance);
     *  2) query/filter/pagination/body parameters statically discovered
     *     from the method BODY, a dedicated FormRequest's rules(), or (as
     *     a last resort) the target model's $fillable - see
     *     ControllerRequestParameterAnalyzer for the full priority order.
     *     Without this, a typical `index(Request $request)` or
     *     `store(Request $request)` endpoint would expose a completely
     *     empty schema: the AI would have no `per_page`/`page`/`sort_by`
     *     parameter to satisfy "show top 20 users", no `name`/`email`
     *     parameter to satisfy "create a user named X", and even if it
     *     guessed one, ArgumentValidator's whitelist would silently strip
     *     it since it wasn't declared.
     *
     * @param ReflectionMethod $method
     * @param string $resourceName plural snake resource name (e.g. "users"), used to guess
     *        the target Eloquent model class for the $fillable fallback - see
     *        ControllerRequestParameterAnalyzer::discoverFromModelFillable().
     * @return array{properties: array<string, mixed>, required: array<string>}
     */
    protected function extractMethodParameters(ReflectionMethod $method, string $resourceName = ''): array
    {
        $properties = [];
        $required = [];

        foreach ($method->getParameters() as $param) {
            $paramName = $param->getName();
            $paramType = 'STRING';
            $paramDescription = "Parameter: {$paramName}";

            if ($param->hasType()) {
                $type = $param->getType();
                if ($type instanceof ReflectionNamedType) {
                    $typeName = $type->getName();

                    if ($typeName === Request::class || is_subclass_of($typeName, Request::class)) {
                        continue;
                    }

                    if (!$type->isBuiltin() && is_subclass_of($typeName, Model::class)) {
                        // Route-model-binding parameter (e.g. `show(User $user)`,
                        // `update(User $user, Request $request)`, `destroy(User $user)`).
                        // The AI only ever needs to supply the record's ID - the
                        // actual model instance is resolved during execution.
                        $paramType = 'INTEGER';
                        $paramDescription = 'The unique ID of the ' . class_basename($typeName) . ' record to find, update, or delete.';
                    } elseif ($paramName === 'id' || str_ends_with($paramName, '_id')) {
                        $paramType = match ($typeName) {
                            'int' => 'INTEGER',
                            'bool', 'boolean' => 'BOOLEAN',
                            'float', 'double' => 'NUMBER',
                            'array' => 'ARRAY',
                            default => 'STRING',
                        };
                        $paramDescription = 'The unique ID of the record to find, update, or delete.';
                    } else {
                        $paramType = match ($typeName) {
                            'int' => 'INTEGER',
                            'bool', 'boolean' => 'BOOLEAN',
                            'float', 'double' => 'NUMBER',
                            'array' => 'ARRAY',
                            default => 'STRING',
                        };
                    }
                }
            } elseif ($paramName === 'id' || str_ends_with($paramName, '_id')) {
                $paramDescription = 'The unique ID of the record to find, update, or delete.';
            }

            $properties[$paramName] = [
                'type' => $paramType,
                'description' => $paramDescription,
            ];

            if ($paramType === 'ARRAY') {
                // Gemini (and other providers) reject an ARRAY-type schema
                // property with no 'items' sub-schema; a bare `array $x`
                // signature type gives no hint of the element type, so
                // default to STRING items.
                $properties[$paramName]['items'] = ['type' => 'STRING'];
            }

            if (!$param->isOptional()) {
                $required[] = $paramName;
            }
        }

        $modelClassHint = $resourceName !== '' ? $this->guessModelClass($resourceName) : null;

        foreach ($this->parameterAnalyzer->discover($method, $modelClassHint) as $name => $discovered) {
            // Signature-typed parameters always win over a body-discovered
            // guess of the same name.
            if (array_key_exists($name, $properties)) {
                continue;
            }

            $properties[$name] = [
                'type' => $discovered['type'],
                'description' => $discovered['description'],
            ];

            if (array_key_exists('default', $discovered)) {
                $properties[$name]['default'] = $discovered['default'];
            }

            if ($discovered['type'] === 'ARRAY') {
                $properties[$name]['items'] = $discovered['items'] ?? ['type' => 'STRING'];
            }

            if (!empty($discovered['required'])) {
                $required[] = $name;
            }
        }

        return [
            // Empty properties must serialize as {} (stdClass), never [] - some
            // providers reject an array where an object schema is expected.
            'properties' => empty($properties) ? new \stdClass() : $properties,
            'required' => $required,
        ];
    }

    /**
     * Best-effort guess at the Eloquent model class backing a resource
     * name (e.g. "users" -> App\Models\User, falling back to App\User for
     * older Laravel app structures). Returns null if neither exists.
     */
    protected function guessModelClass(string $resourceName): ?string
    {
        $studlySingular = Str::studly(Str::singular($resourceName));

        foreach (["App\\Models\\{$studlySingular}", "App\\{$studlySingular}"] as $candidate) {
            if (class_exists($candidate) && is_subclass_of($candidate, Model::class)) {
                return $candidate;
            }
        }

        return null;
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
            // Kept distinct from 'togglestatus' below - conflating them into
            // one description made it hard for the AI to tell "update the
            // name" and "toggle the status" apart when both tools existed
            // on the same controller, sometimes picking the wrong one.
            in_array($normalized, ['update', 'edit', 'bulkupdate', 'updateall']) => "Update one or more fields of an existing {$label} record.",
            $normalized === 'togglestatus' => "Toggle or change only the status (e.g. active/inactive) of an existing {$label} record.",
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
