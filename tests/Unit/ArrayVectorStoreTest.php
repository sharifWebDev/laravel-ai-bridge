<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Unit;

use Sharifuddin\LaravelAiBridge\DTO\VectorRecord;
use Sharifuddin\LaravelAiBridge\Tests\TestCase;
use Sharifuddin\LaravelAiBridge\VectorStore\ArrayVectorStore;

class ArrayVectorStoreTest extends TestCase
{
    public function test_search_ranks_by_cosine_similarity(): void
    {
        $store = new ArrayVectorStore();
        $store->upsert(new VectorRecord('a', [1, 0, 0], [], 'text a', 'hash-a'));
        $store->upsert(new VectorRecord('b', [0, 1, 0], [], 'text b', 'hash-b'));
        $store->upsert(new VectorRecord('c', [0.9, 0.1, 0], [], 'text c', 'hash-c'));

        $results = $store->search([1, 0, 0], 2);

        $this->assertCount(2, $results);
        $this->assertSame('a', $results[0]->id);
        $this->assertEqualsWithDelta(1.0, $results[0]->score, 0.0001);
        $this->assertSame('c', $results[1]->id);
    }

    public function test_metadata_filters_exclude_non_matching_records(): void
    {
        $store = new ArrayVectorStore();
        $store->upsert(new VectorRecord('a', [1, 0], ['category' => 'users'], 'a', 'h1'));
        $store->upsert(new VectorRecord('b', [1, 0], ['category' => 'orders'], 'b', 'h2'));

        $results = $store->search([1, 0], 10, ['category' => 'orders']);

        $this->assertCount(1, $results);
        $this->assertSame('b', $results[0]->id);
    }

    public function test_delete_removes_record_from_search_results(): void
    {
        $store = new ArrayVectorStore();
        $store->upsert(new VectorRecord('a', [1, 0], [], 'a', 'h1'));
        $store->delete('a');

        $this->assertCount(0, $store->search([1, 0], 10));
    }

    public function test_persists_across_new_instances_via_cache(): void
    {
        $storeOne = new ArrayVectorStore();
        $storeOne->upsert(new VectorRecord('a', [1, 0], [], 'a', 'h1'));

        $storeTwo = new ArrayVectorStore();
        $results = $storeTwo->search([1, 0], 10);

        $this->assertCount(1, $results);
    }
}
