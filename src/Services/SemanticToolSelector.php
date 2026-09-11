<?php

namespace Sharifuddin\LaravelAiBridge\Services;

use Illuminate\Support\Facades\Auth;
use Sharifuddin\LaravelAiBridge\Contracts\ToolSelectorInterface;

class SemanticToolSelector implements ToolSelectorInterface
{
    /**
     * Filter and select relevant tools based on user prompt and authorization.
     *
     * @param string $userPrompt
     * @param array<string, mixed> $tools
     * @return array
     */
    public function selectRelevantTools(string $userPrompt, array $tools): array
    {
        $user = Auth::user();
        $prompt = strtolower($userPrompt);
        $scoredTools = [];

        foreach ($tools as $toolKey => $tool) {
            // Permission check: if tool has permission requirement and user is authenticated
            if (!empty($tool['permission']) && $user) {
                $hasPermission = method_exists($user, 'can')
                    ? $user->can($tool['permission'])
                    : true;

                if (!$hasPermission) {
                    continue;
                }
            }

            $toolName = strtolower($tool['name'] ?? '');
            $toolDesc = strtolower($tool['description'] ?? '');

            $keywords = preg_split('/\s+/', $toolDesc, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $matchScore = 0;

            foreach ($keywords as $word) {
                $cleanWord = trim($word, ".,:;()[]{}'\"");
                if (strlen($cleanWord) >= 3 && str_contains($prompt, $cleanWord)) {
                    $matchScore++;
                }
            }

            if (!empty($toolName) && str_contains($prompt, $toolName)) {
                $matchScore += 3;
            }

            // Also check prompt words against description
            $promptWords = preg_split('/\s+/', $prompt, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($promptWords as $pWord) {
                $cleanPWord = trim($pWord, ".,:;()[]{}'\"");
                if (strlen($cleanPWord) >= 3 && str_contains($toolDesc, $cleanPWord)) {
                    $matchScore++;
                }
            }

            if ($matchScore > 0) {
                $scoredTools[] = [
                    'score' => $matchScore,
                    'tool' => $tool,
                ];
            }
        }

        // Sort scored tools by score descending
        usort($scoredTools, fn($a, $b) => $b['score'] <=> $a['score']);

        $selectedTools = array_map(fn($item) => $item['tool'], $scoredTools);

        // Fallback: If no direct keyword match, provide up to 5 available tools
        if (empty($selectedTools) && !empty($tools)) {
            $selectedTools = array_slice(array_values($tools), 0, 5);
        }

        // Format declarations strictly for Gemini API function_declarations schema (snake_case)
        $declarations = [];
        $seenNames = [];

        foreach ($selectedTools as $tool) {
            $rawName = $tool['name'] ?? 'unknown';

            // ইউনিক নাম তৈরির জন্য, যদি কন্ট্রোলার তথ্য থাকে তবেই
            // ControllerName_MethodName ফরম্যাট ব্যবহার করা হয়, নাহলে
            // টুলের নাম অপরিবর্তিত থাকে।
            $controllerName = isset($tool['controller']) ? class_basename($tool['controller']) : null;
            $uniqueName = $controllerName
                ? preg_replace('/[^a-zA-Z0-9_]/', '_', $controllerName . '_' . $rawName)
                : preg_replace('/[^a-zA-Z0-9_]/', '_', $rawName);

            if (isset($seenNames[$uniqueName])) {
                continue;
            }
            $seenNames[$uniqueName] = true;

            $properties = $tool['parameters']['properties'] ?? [];
            if (empty($properties)) {
                $properties = new \stdClass();
            }

            $declarations[] = [
                'name' => $uniqueName, // যেমন: SalesController_index
                'description' => $tool['description'] ?? "Execute {$rawName} on {$controllerName}",
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => $properties,
                ],
            ];
        }

        return [
            [
                'function_declarations' => $declarations,
            ],
        ];
    }
}
