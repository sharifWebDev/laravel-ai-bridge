<?php

namespace Sharifuddin\LaravelAiBridge\Execution;

use Illuminate\Support\Facades\Validator;
use Sharifuddin\LaravelAiBridge\Contracts\ToolInterface;
use Sharifuddin\LaravelAiBridge\Exceptions\ToolValidationException;

/**
 * Strictly validates AI-generated tool arguments against the tool's
 * declared parametersSchema() using Laravel's Validator - never trusting
 * types, ranges, or the presence of arbitrary extra keys from AI output.
 * Unknown arguments are silently dropped (whitelist, not blacklist) and
 * declared defaults are applied for missing optional parameters.
 */
final class ArgumentValidator
{
    /**
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     *
     * @throws ToolValidationException
     */
    public function validate(ToolInterface $tool, array $arguments): array
    {
        $schema = $tool->parametersSchema();

        // Whitelist: only arguments explicitly declared by the tool are
        // ever considered, regardless of what the AI model sent.
        $arguments = array_intersect_key($arguments, $schema);

        $rules = [];
        foreach ($schema as $name => $definition) {
            $rules[$name] = $this->buildRules($definition);
        }

        $validator = Validator::make($arguments, $rules);

        if ($validator->fails()) {
            throw new ToolValidationException(
                "Invalid arguments for tool [{$tool->name()}]: " . $validator->errors()->first(),
                $validator->errors()->toArray()
            );
        }

        $validated = $validator->validated();

        foreach ($schema as $name => $definition) {
            if (!array_key_exists($name, $validated) && array_key_exists('default', $definition)) {
                $validated[$name] = $definition['default'];
            }
        }

        return $validated;
    }

    /** @param array<string, mixed> $definition */
    private function buildRules(array $definition): array
    {
        $rules = [];

        $rules[] = !empty($definition['required']) ? 'required' : 'nullable';

        $type = strtolower($definition['type'] ?? 'string');
        $rules[] = match ($type) {
            'integer', 'int' => 'integer',
            'number', 'float', 'double' => 'numeric',
            'boolean', 'bool' => 'boolean',
            'array' => 'array',
            default => 'string',
        };

        if (isset($definition['min'])) {
            $rules[] = 'min:' . $definition['min'];
        }
        if (isset($definition['max'])) {
            // Bounded maximum is critical protection against AI-supplied
            // values like per_page = 999999.
            $rules[] = 'max:' . $definition['max'];
        }
        if (!empty($definition['enum'])) {
            $rules[] = \Illuminate\Validation\Rule::in($definition['enum']);
        }

        return $rules;
    }
}
