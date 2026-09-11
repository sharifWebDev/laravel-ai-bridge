<?php

namespace Sharifuddin\LaravelAiBridge\Retrieval;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Sharifuddin\LaravelAiBridge\Caching\CacheKeyBuilder;
use Sharifuddin\LaravelAiBridge\Contracts\EmbeddingProviderInterface;
use Sharifuddin\LaravelAiBridge\Contracts\ToolRegistryInterface;
use Sharifuddin\LaravelAiBridge\Contracts\VectorStoreInterface;
use Sharifuddin\LaravelAiBridge\DTO\RetrievalCandidate;
use Sharifuddin\LaravelAiBridge\DTO\RetrievalResult;
use Sharifuddin\LaravelAiBridge\Events\CacheHit;
use Sharifuddin\LaravelAiBridge\Events\CacheMiss;
use Sharifuddin\LaravelAiBridge\Events\ToolsRetrieved;
use Sharifuddin\LaravelAiBridge\Exceptions\EmbeddingException;
use Sharifuddin\LaravelAiBridge\Exceptions\VectorStoreException;
use Sharifuddin\LaravelAiBridge\Execution\ExecutionContext;
use Sharifuddin\LaravelAiBridge\Security\PermissionFilter;

/**
 * The core retrieval pipeline described in the package's architecture:
 *
 *   normalize -> embed -> vector search -> hybrid (semantic + lexical)
 *   scoring -> permission/tenant filtering -> ranking -> top-K selection
 *   -> confidence/ambiguity check -> (cached) RetrievalResult
 *
 * Designed so the AI model only ever sees the smallest useful set of tool
 * declarations, filtered to what the current user is actually allowed to
 * use, never the full tool registry.
 */
final class ToolRetriever
{
    public function __construct(
        private readonly ToolRegistryInterface $registry,
        private readonly EmbeddingProviderInterface $embeddings,
        private readonly VectorStoreInterface $vectorStore,
        private readonly QueryNormalizer $normalizer,
        private readonly LexicalScorer $lexicalScorer,
        private readonly PermissionFilter $permissionFilter,
    ) {
    }

    public function retrieve(string $query, ExecutionContext $context): RetrievalResult
    {
        $normalized = $this->normalizer->normalize($query);

        $cacheKey = CacheKeyBuilder::retrieval(
            $normalized->normalized,
            $this->registry->version(),
            $context->securityFingerprint(),
            $this->embeddings->identifier(),
        );

        if (config('ai-bridge.cache.enabled', true) && ($cached = $this->readCache($cacheKey)) !== null) {
            event(new CacheHit('retrieval', $cacheKey));

            return $cached;
        }

        event(new CacheMiss('retrieval', $cacheKey));

        $result = $this->buildResult($normalized, $context);

        if (config('ai-bridge.cache.enabled', true)) {
            $ttl = (int) config('ai-bridge.cache.ttls.retrieval', 300);
            Cache::put($cacheKey, $this->serialize($result), $ttl);
        }

        event(new ToolsRetrieved($normalized->normalized, $result->toArray(), $result->isAmbiguous, $result->isLowConfidence));

        return $result;
    }

    private function buildResult(NormalizedQuery $normalized, ExecutionContext $context): RetrievalResult
    {
        $allTools = $this->registry->all();

        // Only ever score/rank tools the caller is actually permitted to
        // use, so the AI model never even learns disallowed tools exist.
        $allowedTools = array_filter($allTools, function ($tool) use ($context) {
            if ($context->user !== null && !$this->permissionFilter->allowed($tool, $context)) {
                return false;
            }

            return $this->permissionFilter->tenantAllowed($tool, $context);
        });

        if (empty($allowedTools)) {
            return new RetrievalResult($normalized->normalized, [], false, true, false);
        }

        $semanticScores = [];
        $usedVectorSearch = false;

        try {
            $queryVector = $this->embeddings->embed($normalized->normalized);
            $topK = (int) config('ai-bridge.retrieval.vector_top_k', 20);
            $hits = $this->vectorStore->search($queryVector, $topK);
            foreach ($hits as $hit) {
                $semanticScores[$hit->id] = $hit->score;
            }
            $usedVectorSearch = true;
        } catch (EmbeddingException|VectorStoreException $e) {
            // Fallback strategy: never hard-fail retrieval. Proceed with
            // lexical-only scoring and let the caller/logs know why.
            Log::warning('[ai-bridge] Vector retrieval unavailable, falling back to lexical-only: ' . $e->getMessage());
        }

        $semanticWeight = (float) config('ai-bridge.retrieval.weights.semantic', 0.6);
        $lexicalWeight = (float) config('ai-bridge.retrieval.weights.lexical', 0.3);
        $metadataWeight = (float) config('ai-bridge.retrieval.weights.metadata', 0.1);
        $minSimilarity = (float) config('ai-bridge.retrieval.min_similarity', 0.15);
        $topK = (int) config('ai-bridge.retrieval.top_k', 5);

        $candidates = [];
        foreach ($allowedTools as $tool) {
            $semanticScore = $semanticScores[$tool->name()] ?? 0.0;
            $lexicalScore = $this->lexicalScorer->score($normalized, $tool);
            $metadataScore = $this->exactMentionBonus($normalized, $tool->name());

            if (empty($semanticScores)) {
                $finalScore = ($lexicalScore * 0.8) + ($metadataScore * 0.2);
            } else {
                $finalScore = ($semanticScore * $semanticWeight)
                    + ($lexicalScore * $lexicalWeight)
                    + ($metadataScore * $metadataWeight);
            }

            if ($finalScore <= 0.0 && $lexicalScore <= 0.0 && $metadataScore <= 0.0) {
                continue;
            }

            if ($finalScore < $minSimilarity) {
                continue;
            }

            $candidates[] = new RetrievalCandidate($tool, $semanticScore, $lexicalScore, $metadataScore, $finalScore);
        }

        usort($candidates, fn (RetrievalCandidate $a, RetrievalCandidate $b) => $b->finalScore <=> $a->finalScore);

        $maxCandidates = (int) config('ai-bridge.retrieval.max_candidates', 10);
        $candidates = array_slice($candidates, 0, min($topK, $maxCandidates));

        $confidenceThreshold = (float) config('ai-bridge.retrieval.confidence_threshold', 0.55);
        $scoreMargin = (float) config('ai-bridge.retrieval.score_margin', 0.08);

        $top = $candidates[0]->finalScore ?? 0.0;
        $second = $candidates[1]->finalScore ?? 0.0;

        $isLowConfidence = empty($candidates) || $top < $confidenceThreshold;
        $isAmbiguous = count($candidates) >= 2 && ($top - $second) < $scoreMargin;

        return new RetrievalResult($normalized->normalized, $candidates, $isAmbiguous, $isLowConfidence, $usedVectorSearch);
    }

    private function exactMentionBonus(NormalizedQuery $normalized, string $toolName): float
    {
        $needle = str_replace('_', ' ', $toolName);

        return str_contains($normalized->normalized, $needle) ? 1.0 : 0.0;
    }

    /** @return array<string, mixed> */
    private function serialize(RetrievalResult $result): array
    {
        return [
            'normalized_query' => $result->normalizedQuery,
            'is_ambiguous' => $result->isAmbiguous,
            'is_low_confidence' => $result->isLowConfidence,
            'used_vector_search' => $result->usedVectorSearch,
            'candidates' => array_map(fn ($c) => [
                'name' => $c->tool->name(),
                'semantic_score' => $c->semanticScore,
                'lexical_score' => $c->lexicalScore,
                'metadata_score' => $c->metadataScore,
                'final_score' => $c->finalScore,
            ], $result->candidates),
        ];
    }

    private function readCache(string $key): ?RetrievalResult
    {
        $cached = Cache::get($key);
        if (!is_array($cached)) {
            return null;
        }

        $candidates = [];
        foreach ($cached['candidates'] as $entry) {
            $tool = $this->registry->get($entry['name']);
            if (!$tool) {
                // Registry changed since caching (tool removed) - treat as a
                // full cache miss rather than serving a stale/incomplete set.
                return null;
            }
            $candidates[] = new RetrievalCandidate(
                $tool,
                $entry['semantic_score'],
                $entry['lexical_score'],
                $entry['metadata_score'],
                $entry['final_score'],
            );
        }

        return new RetrievalResult(
            $cached['normalized_query'],
            $candidates,
            $cached['is_ambiguous'],
            $cached['is_low_confidence'],
            $cached['used_vector_search'],
        );
    }
}
