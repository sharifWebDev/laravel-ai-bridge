<?php

namespace Sharifuddin\LaravelAiBridge\Tools;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use ReflectionMethod;
use Sharifuddin\LaravelAiBridge\Contracts\ToolInterface;
use Sharifuddin\LaravelAiBridge\Execution\ExecutionContext;
use Sharifuddin\LaravelAiBridge\Exceptions\ToolExecutionException;

/**
 * Wraps a legacy array-shaped tool definition (as produced by
 * CachedControllerToolProvider / ToolProviderInterface::getTools()) so it
 * can flow through the same registry, retrieval, validation and execution
 * pipeline as first-class ToolInterface implementations.
 *
 * This is what keeps the "reflect on any controller" backward-compatible
 * path fully working: existing host applications that never migrate to
 * AbstractTool keep functioning, and their controllers are now retrieved
 * semantically and re-authorized before execution, instead of being
 * blindly reflection-invoked.
 */
final class LegacyControllerToolAdapter implements ToolInterface
{
    /** @param array<string, mixed> $definition */
    public function __construct(private readonly string $toolKey, private readonly array $definition)
    {
    }

    public function name(): string
    {
        $controller = class_basename($this->definition['controller'] ?? 'App');
        $action = $this->definition['name'] ?? $this->toolKey;

        return \Illuminate\Support\Str::snake(preg_replace('/[^a-zA-Z0-9_]/', '_', "{$controller}_{$action}"));
    }

    public function description(): string
    {
        return $this->definition['description'] ?? "Execute {$this->toolKey}";
    }

    public function category(): string
    {
        return class_basename($this->definition['controller'] ?? 'legacy');
    }

    public function parametersSchema(): array
    {
        $properties = $this->definition['parameters']['properties'] ?? [];
        $required = $this->definition['parameters']['required'] ?? [];

        if ($properties instanceof \stdClass) {
            $properties = (array) $properties;
        }

        $schema = [];
        foreach ($properties as $name => $prop) {
            $schema[$name] = [
                'type' => $this->mapLegacyType($prop['type'] ?? 'STRING'),
                'description' => $prop['description'] ?? "Parameter: {$name}",
                'required' => in_array($name, $required, true),
            ];

            if (array_key_exists('default', $prop)) {
                $schema[$name]['default'] = $prop['default'];
            }

            if (!empty($prop['enum'])) {
                $schema[$name]['enum'] = array_values($prop['enum']);
            }

            if (($schema[$name]['type'] ?? '') === 'array') {
                // Gemini (and other providers) require an 'items' sub-schema
                // whenever type is array - default to string items when the
                // discovery layer didn't infer a more specific element type.
                $itemType = $this->mapLegacyType($prop['items']['type'] ?? 'STRING');
                $schema[$name]['items'] = ['type' => $itemType];
            }
        }

        return $schema;
    }

    public function permission(): ?string
    {
        return $this->definition['permission'] ?? null;
    }

    public function requiresTenant(): bool
    {
        return false;
    }

    /**
     * Builds the text that gets embedded into a vector for this tool.
     *
     * For "index"-style (list/browse) actions this deliberately produces a
     * richer/longer text than for other actions: these are the endpoints
     * that carry filter/pagination/sort parameters discovered from the
     * controller body (see ControllerRequestParameterAnalyzer), and the
     * queries that should match them ("show top 20 users", "list active
     * customers sorted by name", ...) use vocabulary (numbers, "top",
     * "sort", "filter", "search"...) that never appears in a short
     * name+description string. Feeding that vocabulary into the embedded
     * text - plus the resource aliases already computed for this tool -
     * gives vector search a much bigger, more matchable surface for
     * exactly the queries that were previously being missed.
     */
    public function embeddingText(): string
    {
        $parts = [$this->name(), $this->description(), 'category: ' . $this->category()];

        $metadata = $this->metadata();
        if (!empty($metadata['aliases']) && is_array($metadata['aliases'])) {
            $parts[] = 'also known as: ' . implode(', ', $metadata['aliases']);
        }

        $paramNames = [];
        foreach ($this->parametersSchema() as $name => $rule) {
            $parts[] = "parameter {$name}: " . ($rule['description'] ?? $name);
            $paramNames[] = $name;
        }

        $action = strtolower((string) ($metadata['action'] ?? ''));
        $isIndexAction = in_array($action, ['index', 'list', 'all', 'indexall', 'listall'], true);

        if ($isIndexAction && !empty($paramNames)) {
            $parts[] = 'Supports filtering, sorting and pagination via: ' . implode(', ', $paramNames) . '.';
            $parts[] = 'Can satisfy requests such as: show the top N records, list the first N results, '
                . 'return a custom number of records per page, go to a specific page, search or filter the list, '
                . 'sort the list by a field, show only records matching a status.';
        }

        return implode('. ', $parts);
    }

    public function metadata(): array
    {
        $base = [
            'legacy' => true,
            'controller' => $this->definition['controller'] ?? null,
            'action' => $this->definition['action'] ?? $this->definition['name'] ?? null,
        ];

        if (!empty($this->definition['metadata']) && is_array($this->definition['metadata'])) {
            return array_merge($base, $this->definition['metadata']);
        }

        return $base;
    }

    public function authorize(mixed $user, array $arguments, ExecutionContext $context): bool
    {
        return true;
    }

    /**
     * Invokes the original controller method via reflection, supporting
     * Request/FormRequest injection the same way the legacy AiController
     * did - but now only ever reached after ToolExecutor has validated
     * arguments and re-checked authorization.
     */
    public function execute(array $arguments, ExecutionContext $context): mixed
    {
        $controllerClass = $this->definition['controller'] ?? null;
        $methodName = $this->definition['action'] ?? $this->definition['name'] ?? null;

        if (!$controllerClass || !$methodName || !class_exists($controllerClass) || !method_exists($controllerClass, $methodName)) {
            throw new ToolExecutionException("Legacy tool [{$this->toolKey}] no longer maps to an existing controller method.");
        }

        $reflectionMethod = new ReflectionMethod($controllerClass, $methodName);
        $passedArguments = [];
        $request = app('request');

        foreach ($reflectionMethod->getParameters() as $param) {
            $paramType = $param->getType();

            if ($paramType && !$paramType->isBuiltin()) {
                $typeName = $paramType->getName();

                if ($typeName === Request::class || is_subclass_of($typeName, Request::class)) {
                    $formRequest = new $typeName();

                    // AI-supplied arguments (e.g. per_page/page/search/sort_by
                    // discovered by ControllerRequestParameterAnalyzer) are
                    // merged into BOTH the query and request("POST") bags.
                    // Controllers read list/filter parameters inconsistently
                    // - some via $request->query(), others via ->input()/
                    // ->get() - and query() only ever looks at the query bag.
                    // Merging into just one bag (as before) meant an argument
                    // the AI correctly filled in (e.g. "top 20" -> per_page=20)
                    // could still silently have no effect depending on which
                    // accessor the controller happened to use.
                    $formRequest->initialize(
                        array_merge($request->query->all(), $arguments),
                        array_merge($request->request->all(), $arguments),
                        $request->attributes->all(),
                        $request->cookies->all(),
                        $request->files->all(),
                        $request->server->all()
                    );

                    if (method_exists($formRequest, 'setUserResolver')) {
                        $formRequest->setUserResolver($request->getUserResolver());
                    }

                    // A FormRequest's own validateResolved() - which is what
                    // actually runs authorize()/rules() - needs a container
                    // bound to itself: FormRequest::passesAuthorization()
                    // calls $this->container->call([$this, 'authorize']),
                    // and getValidatorInstance() calls
                    // $this->container->make(ValidationFactory::class).
                    // Laravel's own router sets this via setContainer()
                    // when it resolves the FormRequest for a real HTTP
                    // request; since we construct it manually here, that
                    // never happens on its own - without this, authorize()
                    // (which almost every FormRequest defines) crashes with
                    // "Call to a member function call() on null" before the
                    // controller method, or even validation, ever runs.
                    if (method_exists($formRequest, 'setContainer')) {
                        $formRequest->setContainer(app());
                    }

                    if (method_exists($formRequest, 'setRedirector') && app()->bound(\Illuminate\Routing\Redirector::class)) {
                        $formRequest->setRedirector(app(\Illuminate\Routing\Redirector::class));
                    }

                    if (method_exists($formRequest, 'validateResolved')) {
                        try {
                            $formRequest->validateResolved();
                        } catch (\Illuminate\Validation\ValidationException $e) {
                            // Surface the actual field-level messages rather
                            // than letting ToolExecutor's generic catch wrap
                            // this into an unhelpful "The given data was
                            // invalid." with no indication of which field or
                            // why - which is exactly what the AI needs to
                            // correct its next attempt.
                            $details = collect($e->errors())
                                ->map(fn (array $messages, string $field) => "{$field}: " . implode(' ', $messages))
                                ->implode(' | ');

                            throw new ToolExecutionException("Validation failed - {$details}");
                        }
                    }

                    $passedArguments[] = $formRequest;
                    continue;
                }

                if (is_subclass_of($typeName, Model::class)) {
                    // Route-model-binding parameter (e.g. `show(User $user)`,
                    // `update(User $user, Request $request)`, `destroy(User $user)`).
                    // The AI only ever supplies the record's ID (see
                    // CachedControllerToolProvider::extractMethodParameters());
                    // resolve the actual model instance here rather than
                    // passing the raw ID through, since the controller
                    // signature requires a real model object.
                    $paramName = $param->getName();
                    $identifier = $arguments[$paramName] ?? null;

                    if ($identifier === null || $identifier === '') {
                        throw new ToolExecutionException(
                            "Missing required identifier for [{$paramName}] ({$typeName})."
                        );
                    }

                    $model = $typeName::find($identifier);

                    if ($model === null) {
                        throw new ToolExecutionException(
                            class_basename($typeName) . " with id [{$identifier}] was not found."
                        );
                    }

                    $passedArguments[] = $model;
                    continue;
                }
            }

            $paramName = $param->getName();
            if (array_key_exists($paramName, $arguments)) {
                $passedArguments[] = $arguments[$paramName];
            } else {
                $passedArguments[] = $param->isOptional() ? $param->getDefaultValue() : null;
            }
        }

        $controllerInstance = app($controllerClass);

        return $reflectionMethod->invokeArgs($controllerInstance, $passedArguments);
    }

    private function mapLegacyType(string $type): string
    {
        return match (strtoupper($type)) {
            'INTEGER' => 'integer',
            'NUMBER' => 'number',
            'BOOLEAN' => 'boolean',
            'ARRAY' => 'array',
            default => 'string',
        };
    }
}
