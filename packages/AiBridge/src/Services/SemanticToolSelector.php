<?php

namespace App\Services;

use App\Contracts\ToolSelectorInterface;
use Illuminate\Support\Facades\Auth;

class SemanticToolSelector implements ToolSelectorInterface
{
    public function selectRelevantTools(string $userPrompt, array $tools): array
    {
        $user = Auth::user();
        $prompt = strtolower($userPrompt);
        $selectedTools = [];

        foreach ($tools as $name => $tool) {
            if (isset($tool['permission']) && $user) {
                $hasPermission = method_exists($user, 'can') 
                    ? $user->can($tool['permission']) 
                    : true;

                if (!$hasPermission) {
                    continue;
                }
            }

            $keywords = explode(' ', strtolower($tool['description']));
            $matchScore = 0;
            
            foreach ($keywords as $word) {
                if (strlen($word) > 3 && str_contains($prompt, $word)) {
                    $matchScore++;
                }
            }

            if ($matchScore > 0 || str_contains($prompt, strtolower($name))) {
                $selectedTools[] = $tool;
            }
        }

        if (empty($selectedTools)) {
            $selectedTools = array_slice(array_values($tools), 0, 3);
        }

        return [["function_declarations" => $selectedTools]];
    }
}
