<?php

namespace App\Contracts;

interface PromptProcessorInterface
{
    public function processPrompt(string $userPrompt): array;
}
