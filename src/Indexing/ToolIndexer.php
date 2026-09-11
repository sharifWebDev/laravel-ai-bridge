<?php

namespace Sharifuddin\LaravelAiBridge\Indexing;

use Illuminate\Support\Facades\Cache;
use Sharifuddin\LaravelAiBridge\Caching\CacheKeyBuilder;
use Sharifuddin\LaravelAiBridge\Contracts\EmbeddingProviderInterface;
use Sharifuddin\LaravelAiBridge\Contracts\ToolRegistryInterface;
use Sharifuddin\LaravelAiBridge\Contracts\VectorStoreInterface;
use Sharifuddin\LaravelAiBridge\DTO\VectorRecord;
use Sharifuddin\LaravelAiBridge\Exceptions\ToolNotFoundException;

/**
 * Vector indexing lifecycle: initial/incremental indexing, single-tool
 * re-index, and full re-index, with hash-based change detection so an
 * unchanged tool definition never triggers a redundant (costly) embedding
 * call. Backs the `ai:tools:index` / `ai:tools:reindex` Artisan commands.
 */
final class ToolIndexer
{
    public function __construct(
        private readonly ToolRegistryInterface $registry,
        private readonly EmbeddingProviderInterface $embeddings,
        private readonly VectorStoreInterface $vectorStore,
    ) {
    }

    /** @return array{indexed: int, skipped: int, failed: array<int, string>} */
    public function indexAll(bool $force = false, ?callable $onProgress = null): array
    {
        $indexed = 0;
        $skipped = 0;
        $failed = [];

        foreach ($this->registry->all() as $name => $tool) {
            try {
                if ($this->indexOne($name, $force)) {
                    $indexed++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $failed[] = "{$name}: {$e->getMessage()}";
            } finally {
                if ($onProgress !== null) {
                    $onProgress($name);
                }
            }
        }

        return ['indexed' => $indexed, 'skipped' => $skipped, 'failed' => $failed];
    }

    /**
     * Indexes a single tool by name. Returns true if an embedding was
     * (re)generated and upserted, false if it was skipped because the
     * tool's semantic definition hasn't changed since the last index run.
     */
    public function indexOne(string $name, bool $force = false): bool
    {
        $tool = $this->registry->get($name);
        if (!$tool) {
            throw ToolNotFoundException::named($name);
        }

        $embeddingText = $tool->embeddingText();
        $currentHash = sha1($embeddingText);
        $hashKey = CacheKeyBuilder::toolHash($name);
        $previousHash = Cache::get($hashKey);

        if (!$force && $previousHash === $currentHash) {
            return false;
        }

        $vector = $this->embeddings->embed($embeddingText);

        $metadata = array_merge($tool->metadata(), [
            'category' => $tool->category(),
            'permission' => $tool->permission(),
            'requires_tenant' => $tool->requiresTenant(),
        ]);

        $this->vectorStore->upsert(new VectorRecord($name, $vector, $metadata, $embeddingText, $currentHash));

        Cache::forever($hashKey, $currentHash);

        return true;
    }
}
