<?php

namespace Sharifuddin\LaravelAiBridge\Contracts;

use Sharifuddin\LaravelAiBridge\Execution\ExecutionContext;

/**
 * Represents a single AI-callable capability backed by application business
 * logic (an Action, Service, or - for backward compatibility - a legacy
 * controller method). This is the unit that is registered, embedded,
 * retrieved, ranked, validated, authorized and executed by the package.
 */
interface ToolInterface
{
    /**
     * Canonical, unique, snake_case tool identifier (e.g. "list_users").
     * This is the name presented to the AI model and used as the
     * registry/vector-store key.
     */
    public function name(): string;

    /**
     * Short human/AI-facing description of what the tool does.
     */
    public function description(): string;

    /**
     * Domain/category grouping (e.g. "users", "orders", "inventory").
     */
    public function category(): string;

    /**
     * Flat parameter schema consumed by the ArgumentValidator, e.g.:
     * [
     *   'search'   => ['type' => 'string', 'required' => false],
     *   'status'   => ['type' => 'string', 'required' => false, 'enum' => ['active', 'inactive']],
     *   'per_page' => ['type' => 'integer', 'required' => false, 'default' => 20, 'min' => 1, 'max' => 100],
     * ]
     *
     * @return array<string, array<string, mixed>>
     */
    public function parametersSchema(): array;

    /**
     * Laravel ability/gate name required to use this tool, or null if the
     * tool has no specific permission requirement.
     */
    public function permission(): ?string;

    /**
     * Whether this tool must only run within a resolved tenant/company/
     * branch context.
     */
    public function requiresTenant(): bool;

    /**
     * Deterministic, canonical text used to generate the tool's semantic
     * embedding. Must never contain secrets. Changing this text is what
     * triggers re-embedding during indexing.
     */
    public function embeddingText(): string;

    /**
     * Free-form searchable/filterable metadata: aliases, entity, action,
     * synonyms, version, etc. Never put permission-bypassing data here.
     *
     * @return array<string, mixed>
     */
    public function metadata(): array;

    /**
     * Tool-specific authorization hook, evaluated in addition to the
     * permission()/Gate check, immediately before execution. Return false
     * to deny. Default implementations should simply return true.
     *
     * @param array<string, mixed> $arguments
     */
    public function authorize(mixed $user, array $arguments, ExecutionContext $context): bool;

    /**
     * Execute the tool's business logic with validated arguments and
     * return raw data (array, Arrayable, Collection, or scalar). The
     * caller (ToolExecutor) is responsible for normalizing this into a
     * ToolResult and applying size limits.
     *
     * @param array<string, mixed> $arguments
     */
    public function execute(array $arguments, ExecutionContext $context): mixed;
}
