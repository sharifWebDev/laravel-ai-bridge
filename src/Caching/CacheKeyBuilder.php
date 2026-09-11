<?php

namespace Sharifuddin\LaravelAiBridge\Caching;

/**
 * Builds deterministic, safely-namespaced cache keys for every caching
 * layer described in the token-optimization requirements. Every key that
 * could vary by "who is asking" folds in the security fingerprint so one
 * user's/tenant's cached results can never leak to another.
 */
final class CacheKeyBuilder
{
    public static function embedding(string $providerIdentifier, string $normalizedText): string
    {
        return 'ai-bridge:embedding:' . sha1($providerIdentifier . '|' . $normalizedText);
    }

    public static function retrieval(
        string $normalizedQuery,
        string $registryVersion,
        string $securityFingerprint,
        string $providerIdentifier,
    ): string {
        return 'ai-bridge:retrieval:' . sha1(implode('|', [
            $providerIdentifier,
            $registryVersion,
            $securityFingerprint,
            $normalizedQuery,
        ]));
    }

    public static function toolMetadata(string $registryVersion): string
    {
        return "ai-bridge:tool-metadata:{$registryVersion}";
    }

    public static function toolHash(string $toolName): string
    {
        return "ai-bridge:tool-hash:{$toolName}";
    }

    public static function aiResponse(string $normalizedQuery, string $securityFingerprint, string $model): string
    {
        return 'ai-bridge:ai-response:' . sha1($model . '|' . $securityFingerprint . '|' . $normalizedQuery);
    }
}
