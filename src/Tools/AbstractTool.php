<?php

namespace Sharifuddin\LaravelAiBridge\Tools;

use Sharifuddin\LaravelAiBridge\Contracts\ToolInterface;
use Sharifuddin\LaravelAiBridge\Execution\ExecutionContext;

/**
 * Convenience base class for first-class AI tools. Concrete tools
 * typically only need to implement name(), description(), execute(), and
 * parametersSchema(); everything else has a sane default.
 *
 * Example:
 *
 *   class ListUsersTool extends AbstractTool
 *   {
 *       public function name(): string { return 'list_users'; }
 *       public function category(): string { return 'users'; }
 *       public function description(): string
 *       {
 *           return 'List application users with pagination, search, status and role filtering.';
 *       }
 *       public function parametersSchema(): array
 *       {
 *           return [
 *               'search' => ['type' => 'string', 'required' => false],
 *               'status' => ['type' => 'string', 'required' => false, 'enum' => ['active', 'inactive']],
 *               'per_page' => ['type' => 'integer', 'required' => false, 'default' => 20, 'min' => 1, 'max' => 100],
 *           ];
 *       }
 *       public function permission(): ?string { return 'view-users'; }
 *       public function execute(array $arguments, ExecutionContext $context): mixed
 *       {
 *           return app(UserService::class)->list($arguments, $context);
 *       }
 *   }
 */
abstract class AbstractTool implements ToolInterface
{
    abstract public function name(): string;

    abstract public function description(): string;

    public function category(): string
    {
        return 'general';
    }

    public function parametersSchema(): array
    {
        return [];
    }

    public function permission(): ?string
    {
        return null;
    }

    public function requiresTenant(): bool
    {
        return false;
    }

    /**
     * Builds a canonical semantic representation from name, description,
     * category, parameters and metadata aliases/synonyms so retrieval
     * accuracy doesn't depend on the tool name alone. Override for full
     * control.
     */
    public function embeddingText(): string
    {
        $parts = [
            $this->name(),
            $this->description(),
            'category: ' . $this->category(),
        ];

        foreach ($this->parametersSchema() as $param => $rule) {
            $desc = $rule['description'] ?? $param;
            $parts[] = "parameter {$param}: {$desc}";
        }

        $metadata = $this->metadata();
        if (!empty($metadata['aliases'])) {
            $parts[] = 'aliases: ' . implode(', ', (array) $metadata['aliases']);
        }
        if (!empty($metadata['entity'])) {
            $parts[] = 'entity: ' . $metadata['entity'];
        }
        if (!empty($metadata['action'])) {
            $parts[] = 'action: ' . $metadata['action'];
        }

        return implode('. ', $parts);
    }

    public function metadata(): array
    {
        return [];
    }

    public function authorize(mixed $user, array $arguments, ExecutionContext $context): bool
    {
        return true;
    }
}
