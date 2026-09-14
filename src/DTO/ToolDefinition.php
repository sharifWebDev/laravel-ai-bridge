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
     * Provider-agnostic, canonical JSON-Schema style declaration
     * (lowercase types: "object"/"string"/"integer"/...), the same shape
     * OpenAI, DeepSeek, Anthropic and most other function-calling APIs
     * expect natively. Built with only what the AI needs - never
     * permission/tenant/internal metadata.
     *
     * Providers whose wire format differs (e.g. Gemini, which expects
     * uppercase "OBJECT"/"STRING" enum values) are responsible for
     * translating this canonical shape themselves - see
     * GeminiAiProvider::toGeminiDeclarations().
     *
     * @return array<string, mixed>
     */
    public function toDeclaration(): array
    {
        $properties = [];
        $required = [];

        foreach ($this->parameters as $paramName => $rule) {
            $type = $this->mapType($rule['type'] ?? 'string');
            $properties[$paramName] = [
                'type' => $type,
                'description' => $rule['description'] ?? "Parameter: {$paramName}",
            ];

            if ($type === 'array') {
                // OpenAI-compatible AND Gemini schemas both require an
                // 'items' sub-schema whenever type is array - without it
                // Gemini rejects the ENTIRE request (a 422 covering every
                // tool in the call, not just this one), so default to
                // string items when a more specific element type wasn't
                // discovered upstream.
                $itemType = $this->mapType($rule['items']['type'] ?? 'string');
                $properties[$paramName]['items'] = ['type' => $itemType];
            }

            if (!empty($rule['enum'])) {
                $properties[$paramName]['enum'] = array_values($rule['enum']);
            }

            if (array_key_exists('default', $rule)) {
                $properties[$paramName]['default'] = $rule['default'];
            }

            if (!empty($rule['required'])) {
                $required[] = $paramName;
            }
        }

        return [
            'name' => $this->name,
            'description' => $this->description,
            'parameters' => [
                'type' => 'object',
                'properties' => empty($properties) ? new \stdClass() : $properties,
                'required' => $required,
            ],
        ];
    }

    private function mapType(string $type): string
    {
        return match (strtolower($type)) {
            'integer', 'int' => 'integer',
            'number', 'float', 'double' => 'number',
            'boolean', 'bool' => 'boolean',
            'array' => 'array',
            default => 'string',
        };
    }
}
