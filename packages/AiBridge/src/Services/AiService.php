<?php

namespace App\Services;

use App\Contracts\AiProcessorInterface;
use App\Contracts\ToolProviderInterface;
use App\Contracts\ToolSelectorInterface;
use Illuminate\Support\Facades\Http;

class AiService implements AiProcessorInterface
{
    protected ToolProviderInterface $toolProvider;
    protected ToolSelectorInterface $toolSelector;
    protected string $apiKey;

    public function __construct(ToolProviderInterface $toolProvider, ToolSelectorInterface $toolSelector)
    {
        $this->toolProvider = $toolProvider;
        $this->toolSelector = $toolSelector;
        $this->apiKey = config('services.gemini.key');
    }

    public function selectRelevantTools(string $userPrompt, array $tools): array
    {
        return $this->toolSelector->selectRelevantTools($userPrompt, $tools);
    }

    public function processPrompt(string $userPrompt): array
    {
        $allTools = $this->toolProvider->getTools();
        $tools = $this->selectRelevantTools($userPrompt, $allTools);

        if (empty($tools[0]['function_declarations'])) {
            return [
                'unauthorized' => true,
                'message' => 'You do not have the necessary permissions to perform this action.'
            ];
        }

        $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $this->apiKey, [
            "contents" => [["parts" => [["text" => $userPrompt]]]],
            "tools" => $tools
        ]);

        return $response->json();
    }
}
