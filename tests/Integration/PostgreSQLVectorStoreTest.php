<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Integration;

use Sharifuddin\LaravelAiBridge\DTO\VectorRecord;
use Sharifuddin\LaravelAiBridge\Tests\TestCase;
use Sharifuddin\LaravelAiBridge\VectorStore\PostgreSQLVectorStore;

/**
 * Real integration test against a live PostgreSQL + pgvector instance
 * (the package's default vector store). Skips itself if no such server
 * is reachable, so it never breaks CI/dev environments without Postgres,
 * but genuinely exercises CREATE EXTENSION/table bootstrap, upsert, and
 * pgvector cosine-distance search when one is available.
 */
class PostgreSQLVectorStoreTest extends TestCase
{
    private function makeStore(): PostgreSQLVectorStore
    {
        config([
            'ai-bridge.vector.postgresql.connection' => 'pgsql_test',
            'ai-bridge.vector.postgresql.table' => 'ai_tool_vectors_test',
            'ai-bridge.vector.postgresql.dimensions' => 3,
        ]);

        try {
            return new PostgreSQLVectorStore();
        } catch (\Throwable $e) {
            $this->markTestSkipped('No live PostgreSQL/pgvector server available: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        try {
            \Illuminate\Support\Facades\DB::connection('pgsql_test')->statement('DROP TABLE IF EXISTS ai_tool_vectors_test');
        } catch (\Throwable) {
            // best effort cleanup
        }

        parent::tearDown();
    }

    public function test_creates_the_pgvector_extension_and_table_automatically(): void
    {
        $this->makeStore();

        $exists = \Illuminate\Support\Facades\DB::connection('pgsql_test')
            ->getSchemaBuilder()
            ->hasTable('ai_tool_vectors_test');

        $this->assertTrue($exists);
    }

    public function test_upsert_and_cosine_similarity_search_rank_correctly(): void
    {
        $store = $this->makeStore();

        $store->upsert(new VectorRecord('list_users', [1.0, 0.0, 0.0], ['category' => 'users'], 'list users text', 'hash-a'));
        $store->upsert(new VectorRecord('get_order', [0.0, 1.0, 0.0], ['category' => 'orders'], 'get order text', 'hash-b'));
        $store->upsert(new VectorRecord('list_customers', [0.9, 0.1, 0.0], ['category' => 'customers'], 'list customers text', 'hash-c'));

        $results = $store->search([1.0, 0.0, 0.0], 2);

        $this->assertCount(2, $results);
        $this->assertSame('list_users', $results[0]->id);
        $this->assertEqualsWithDelta(1.0, $results[0]->score, 0.0001);
        $this->assertSame('list_customers', $results[1]->id);
    }

    public function test_metadata_filters_are_pushed_down_to_sql(): void
    {
        $store = $this->makeStore();

        $store->upsert(new VectorRecord('a', [1.0, 0.0, 0.0], ['category' => 'users'], 'a', 'h1'));
        $store->upsert(new VectorRecord('b', [1.0, 0.0, 0.0], ['category' => 'orders'], 'b', 'h2'));

        $results = $store->search([1.0, 0.0, 0.0], 10, ['category' => 'orders']);

        $this->assertCount(1, $results);
        $this->assertSame('b', $results[0]->id);
    }

    public function test_upsert_is_idempotent_via_on_conflict_update(): void
    {
        $store = $this->makeStore();

        $store->upsert(new VectorRecord('a', [1.0, 0.0, 0.0], [], 'first', 'h1'));
        $store->upsert(new VectorRecord('a', [0.0, 1.0, 0.0], [], 'second', 'h2'));

        $row = \Illuminate\Support\Facades\DB::connection('pgsql_test')
            ->table('ai_tool_vectors_test')
            ->where('id', 'a')
            ->first();

        $this->assertSame(1, \Illuminate\Support\Facades\DB::connection('pgsql_test')->table('ai_tool_vectors_test')->count());
        $this->assertSame('second', $row->text);
    }

    public function test_delete_removes_the_row(): void
    {
        $store = $this->makeStore();

        $store->upsert(new VectorRecord('a', [1.0, 0.0, 0.0], [], 'a', 'h1'));
        $store->delete('a');

        $this->assertCount(0, $store->search([1.0, 0.0, 0.0], 10));
    }
}
