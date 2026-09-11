<?php

namespace Sharifuddin\LaravelAiBridge\Retrieval;

/**
 * Lightweight, language-agnostic query normalization: trims, lowercases
 * (Unicode-aware, so Bengali/other scripts are preserved rather than
 * mangled), strips punctuation while keeping letters/digits from any
 * script, collapses whitespace, and tokenizes. Deliberately avoids
 * hardcoded per-language rules - semantic quality for phrasing/synonym
 * variation is handled by the embedding model, not by this step.
 */
final class QueryNormalizer
{
    /** @var array<int, string> */
    private const STOPWORDS = ['the', 'a', 'an', 'please', 'me', 'my', 'to', 'of', 'for'];

    public function normalize(string $query): NormalizedQuery
    {
        $trimmed = trim($query);
        $lower = mb_strtolower($trimmed);

        // Strip punctuation but keep any Unicode letter/number/mark and spaces.
        $stripped = preg_replace('/[^\pL\pN\pM\s]+/u', ' ', $lower) ?? $lower;
        $collapsed = trim(preg_replace('/\s+/u', ' ', $stripped) ?? $stripped);

        $tokens = $collapsed === '' ? [] : (preg_split('/\s+/u', $collapsed) ?: []);
        $tokens = array_values(array_filter($tokens, function (string $token) {
            return mb_strlen($token) >= 2 && !in_array($token, self::STOPWORDS, true);
        }));

        return new NormalizedQuery($query, $collapsed, $tokens);
    }
}
