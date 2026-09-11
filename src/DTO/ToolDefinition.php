<?php

namespace Sharifuddin\LaravelAiBridge\DTO;

use Sharifuddin\LaravelAiBridge\Contracts\ToolInterface;

/**
 * Immutable, cache/serialization-friendly snapshot of a ToolInterface,
 * used for AI-provider declarations and for the tool metadata cache so
 * the registry doesn't need to be rebuilt/reflected on every request.
 */
final class ToolDefinition
{
    /**
     * @param array<string, array<string, mixed>> $parameters
     * @param array<string, mixed> $metadata
     */
    private function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $category,
        public readonly array $parameters,
        public readonly ?string $permission,
        public readonly bool $requiresTenant,
        public readonly string $embeddingText,
        public readonly array $metadata,
        public readonly string $hash,
    ) {
    }

    public static function fromTool(ToolInterface $tool): self
    {
        $embeddingText = $tool->embeddingText();

        return new self(
            name: $tool->name(),
            description: $tool->description(),
            category: $tool->category(),
            parameters: $tool->parametersSchema(),
            permission: $tool->permission(),
            requiresTenant: $tool->requiresTenant(),
            embeddingText: $embeddingText,
            metadata: $tool->metadata(),
            hash: sha1($tool->name() . '|' . $embeddingText),
        );
    }

    /**
     * Provider-agnostic "OBJECT" style declaration shape (compatible with
     * Gemini's function_declarations / OpenAI-style tool schemas), built
     * with only what the AI needs - never permission/tenant/internal
     * metadata.
     *
     * @return array<string, mixed>
     */
    public function toDeclaration(): array
    {
        $properties = [];
        $required = [];

        foreach ($this->parameters as $paramName => $rule) {
            $properties[$paramName] = [
                'type' => $this->mapType($rule['type'] ?? 'string'),
                'description' => $rule['description'] ?? "Parameter: {$paramName}",
            ];

            if (!empty($rule['enum'])) {
                $properties[$paramName]['enum'] = array_values($rule['enum']);
            }

            if (!empty($rule['required'])) {
                $required[] = $paramName;
            }
        }

        return [
            'name' => $this->name,
            'description' => $this->description,
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => empty($properties) ? new \stdClass() : $properties,
                'required' => $required,
            ],
        ];
    }

    private function mapType(string $type): string
    {
        return match (strtolower($type)) {
            'integer', 'int' => 'INTEGER',
            'number', 'float', 'double' => 'NUMBER',
            'boolean', 'bool' => 'BOOLEAN',
            'array' => 'ARRAY',
            default => 'STRING',
        };
    }
}
