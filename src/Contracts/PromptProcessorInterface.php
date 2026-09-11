<?php

namespace Sharifuddin\LaravelAiBridge\Contracts;

interface PromptProcessorInterface
{
    /**
     * Process a user prompt against LLM and tools.
     *
     * @param string $userPrompt
     * @return array
     */
    public function processPrompt(string $userPrompt): array;
}
