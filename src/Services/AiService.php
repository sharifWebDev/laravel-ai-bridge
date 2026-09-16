<?php

namespace Sharifuddin\LaravelAiBridge\Services;

use Sharifuddin\LaravelAiBridge\Contracts\AiProcessorInterface;
use Sharifuddin\LaravelAiBridge\Contracts\AiProviderInterface;
use Sharifuddin\LaravelAiBridge\Contracts\ToolProviderInterface;
use Sharifuddin\LaravelAiBridge\Contracts\ToolRegistryInterface;
use Sharifuddin\LaravelAiBridge\Contracts\ToolSelectorInterface;
use Sharifuddin\LaravelAiBridge\DTO\ToolDefinition;
use Sharifuddin\LaravelAiBridge\Events\ToolSelected;
use Sharifuddin\LaravelAiBridge\Exceptions\AiBridgeException;
use Sharifuddin\LaravelAiBridge\Execution\ExecutionContext;
use Sharifuddin\LaravelAiBridge\Execution\ToolExecutor;
use Sharifuddin\LaravelAiBridge\Retrieval\ToolRetriever;

/**
 * Orchestrates the full retrieval -> AI -> validation -> authorization ->
 * execution pipeline via chat(), the package's new recommended entry
 * point.
 *
 * Backward compatibility: this class still implements AiProcessorInterface
 * (selectRelevantTools()/processPrompt()) with their ORIGINAL behavior,
 * unchanged, so any code already depending on the interface keeps working
 * exactly as before. Those legacy methods are additive - chat() is the
 * only thing that changed, and it is what the shipped AiController now
 * uses.
 */
class AiService implements AiProcessorInterface
{
    public function __construct(
        protected ToolProviderInterface $legacyToolProvider,
        protected ToolSelectorInterface $legacyToolSelector,
        protected ToolRegistryInterface $registry,
        protected ToolRetriever $retriever,
        protected ToolExecutor $executor,
        protected AiProviderInterface $aiProvider,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * Preserved for backward compatibility: delegates to the legacy
     * keyword-matching selector exactly as the original implementation
     * did. New code should rely on ToolRetriever (via chat()) instead.
     */
    public function selectRelevantTools(string $userPrompt, array $tools): array
    {
        return $this->legacyToolSelector->selectRelevantTools($userPrompt, $tools);
    }

    /**
     * {@inheritDoc}
     *
     * Preserved for backward compatibility: unchanged direct call to the
     * Gemini generateContent API using the legacy tool provider/selector,
     * with no execution step. New code should use chat() instead, which
     * runs the full hybrid-retrieval + secure-execution pipeline.
     */
    public function processPrompt(string $userPrompt): array
    {

        $legacyProvider = app(\Sharifuddin\LaravelAiBridge\Providers\GeminiAiProvider::class);

        $allTools = $this->legacyToolProvider->getTools();
        $selectedTools = $this->selectRelevantTools($userPrompt, $allTools);

        $declarations = [];
        if (isset($selectedTools[0]['function_declarations'])) {
            $declarations = $selectedTools[0]['function_declarations'];
        } elseif (!empty($selectedTools) && array_keys($selectedTools) !== range(0, count($selectedTools) - 1)) {
            $declarations = array_values($selectedTools);
        } else {
            $declarations = $selectedTools;
        }

        $response = $legacyProvider->chat($userPrompt, $declarations);

        if ($response->isError()) {
            return ['status' => 'error', 'message' => $response->text, 'details' => $response->raw];
        }

        return $response->raw;
    }

    /**
     * New pipeline entry point: normalize -> hybrid-retrieve tools the
     * caller is authorized for -> ask the AI model with only that
     * smallest useful set of declarations -> if it chose a tool, validate
     * + re-authorize + execute it securely -> return a compact, normalized
     * response.
     *
     * @return array{status: string, message?: string, action?: string, result?: array, retrieval?: array}|\Symfony\Component\HttpFoundation\Response
     */
    public function chat(string $prompt, ?ExecutionContext $context = null): array|\Symfony\Component\HttpFoundation\Response
    {
        $context = $context ?? ExecutionContext::fromCurrentRequest();

        $retrieval = $this->retriever->retrieve($prompt, $context);

        $declarations = array_map(
            fn ($candidate) => ToolDefinition::fromTool($candidate->tool)->toDeclaration(),
            $retrieval->candidates
        );

        try {
            $response = $this->aiProvider->chat($prompt, $declarations);
        } catch (AiBridgeException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }

        if ($response->isError()) {
            return ['status' => 'error', 'message' => $response->text];
        }

        if ($response->isToolCall()) {
            event(new ToolSelected($response->toolName, $response->toolArguments));

            try {
                $result = $this->executor->execute($response->toolName, $response->toolArguments, $context);
            } catch (AiBridgeException $e) {
                return ['status' => 'error', 'message' => $e->getMessage()];
            }

            // File downloads (Excel/PDF/CSV exports, ...) are returned
            // straight through - never JSON-wrapped - so the HTTP layer
            // can stream the actual file back to the client.
            if ($result->isBinary()) {
                return $result->binaryResponse;
            }

            return [
                'status' => 'success',
                'action' => $response->toolName,
                'result' => $result->toArray(),
            ];
        }

        $topCandidate = $retrieval->candidates[0] ?? null;
        if ($topCandidate && !$retrieval->isLowConfidence && !$retrieval->isAmbiguous) {
            try {
                $result = $this->executor->execute($topCandidate->tool->name(), [], $context);

                if ($result->isBinary()) {
                    return $result->binaryResponse;
                }

                return [
                    'status' => 'success',
                    'action' => $topCandidate->tool->name(),
                    'result' => $result->toArray(),
                ];
            } catch (AiBridgeException $e) {
                return ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        return ['status' => 'success', 'message' => $response->text];
    }
}
