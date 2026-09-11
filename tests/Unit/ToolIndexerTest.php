<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Unit;

use Sharifuddin\LaravelAiBridge\Contracts\ToolRegistryInterface;
use Sharifuddin\LaravelAiBridge\Indexing\ToolIndexer;
use Sharifuddin\LaravelAiBridge\Tests\Fakes\ListUsersTool;
use Sharifuddin\LaravelAiBridge\Tests\TestCase;

class ToolIndexerTest extends TestCase
{
    public function test_indexes_a_new_tool(): void
    {
        $provider = $this->useFakeEmbeddings(['list_users' => [1.0]]);

        $registry = $this->app->make(ToolRegistryInterface::class);
        $registry->register(new ListUsersTool());

        $indexer = $this->app->make(ToolIndexer::class);
        $indexed = $indexer->indexOne('list_users');

        $this->assertTrue($indexed);
        $this->assertSame(1, $provider->callCount);
    }

    public function test_skips_regenerating_embedding_when_definition_unchanged(): void
    {
        $provider = $this->useFakeEmbeddings(['list_users' => [1.0]]);

        $registry = $this->app->make(ToolRegistryInterface::class);
        $registry->register(new ListUsersTool());

        $indexer = $this->app->make(ToolIndexer::class);
        $indexer->indexOne('list_users');
        $callsAfterFirst = $provider->callCount;

        $again = $indexer->indexOne('list_users');

        $this->assertFalse($again);
        $this->assertSame($callsAfterFirst, $provider->callCount);
    }

    public function test_force_reindex_regenerates_embedding_even_when_unchanged(): void
    {
        $provider = $this->useFakeEmbeddings(['list_users' => [1.0]]);

        $registry = $this->app->make(ToolRegistryInterface::class);
        $registry->register(new ListUsersTool());

        $indexer = $this->app->make(ToolIndexer::class);
        $indexer->indexOne('list_users');
        $callsAfterFirst = $provider->callCount;

        $reindexed = $indexer->indexOne('list_users', force: true);

        $this->assertTrue($reindexed);
        $this->assertSame($callsAfterFirst + 1, $provider->callCount);
    }
}
