<?php

namespace Sharifuddin\LaravelAiBridge\Contracts;

interface ToolSelectorInterface
{
    /**
     * Filter and select relevant tools based on user prompt.
     *
     * @param string $userPrompt
     * @param array<string, mixed> $tools
     * @return array
     */
    public function selectRelevantTools(string $userPrompt, array $tools): array;
}
