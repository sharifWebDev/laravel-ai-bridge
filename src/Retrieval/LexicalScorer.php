<?php

namespace Sharifuddin\LaravelAiBridge\Retrieval;

use Sharifuddin\LaravelAiBridge\Contracts\ToolInterface;

/**
 * Lightweight lexical/keyword signal used alongside vector similarity in
 * the hybrid ranking: exact tool-name mention, alias mention, and token
 * overlap between the query and the tool's name/description/parameters.
 * Returns a score normalized to roughly [0, 1] so it can be weighted
 * consistently against the semantic score.
 */
final class LexicalScorer
{
    public function score(NormalizedQuery $query, ToolInterface $tool): float
    {
        if (empty($query->tokens)) {
            return 0.0;
        }

        $toolName = mb_strtolower(str_replace('_', ' ', $tool->name()));
        $description = mb_strtolower($tool->description());
        $metadata = $tool->metadata();
        $aliases = array_map('mb_strtolower', (array) ($metadata['aliases'] ?? []));

        $score = 0.0;

        // Strong signal: the tool name (or a closely related alias) appears
        // in the query even when extra words like "active" appear between
        // the key terms (e.g. "all active users" vs "all users").
        if ($toolName !== '' && $this->tokenOverlapRatio($query->tokens, $this->tokenizePhrase($toolName)) >= 0.5) {
            $score += 0.6;
        }

        foreach ($aliases as $alias) {
            $alias = trim($alias);
            if ($alias === '') {
                continue;
            }

            if ($this->tokenOverlapRatio($query->tokens, $this->tokenizePhrase($alias)) >= 0.5) {
                $score += 0.4;
                break;
            }
        }

        // Token overlap between the query and name+description+params.
        $haystackTokens = preg_split('/\s+/u', $toolName . ' ' . $description) ?: [];
        foreach (array_keys($tool->parametersSchema()) as $paramName) {
            $haystackTokens[] = mb_strtolower(str_replace('_', ' ', $paramName));
        }
        $haystackTokens = array_filter(array_map('trim', $haystackTokens), fn ($t) => mb_strlen($t) >= 3);

        $matches = 0;
        foreach ($query->tokens as $token) {
            foreach ($haystackTokens as $hay) {
                if (mb_strlen($token) >= 3 && ($this->containsToken($hay, $token) || $this->containsToken($token, $hay))) {
                    $matches++;
                    break;
                }
            }
        }

        $overlapRatio = $matches / max(count($query->tokens), 1);
        $score += min($overlapRatio, 1.0) * 0.4;

        return min($score, 1.0);
    }

    /** @param array<int, string> $tokens */
    private function tokenOverlapRatio(array $queryTokens, array $candidateTokens): float
    {
        if ($candidateTokens === []) {
            return 0.0;
        }

        $matches = 0;
        foreach ($queryTokens as $token) {
            foreach ($candidateTokens as $candidate) {
                if ($this->containsToken($token, $candidate) || $this->containsToken($candidate, $token)) {
                    $matches++;
                    break;
                }
            }
        }

        return $matches / max(count($candidateTokens), 1);
    }

    /** @return array<int, string> */
    private function tokenizePhrase(string $phrase): array
    {
        $phrase = mb_strtolower(trim($phrase));
        if ($phrase === '') {
            return [];
        }

        $tokens = preg_split('/\s+/u', preg_replace('/[^
\pL\pN\pM\s]+/u', ' ', $phrase) ?? $phrase) ?: [];

        return array_values(array_filter($tokens, fn (string $token) => mb_strlen($token) >= 2));
    }

    private function containsToken(string $haystack, string $needle): bool
    {
        if ($haystack === '' || $needle === '') {
            return false;
        }

        return str_contains($haystack, $needle) || str_contains($needle, $haystack);
    }
}
