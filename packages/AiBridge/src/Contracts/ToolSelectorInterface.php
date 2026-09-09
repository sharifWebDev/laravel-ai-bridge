<?php

namespace App\Contracts;

interface ToolSelectorInterface
{
    public function selectRelevantTools(string $userPrompt, array $tools): array;
}
