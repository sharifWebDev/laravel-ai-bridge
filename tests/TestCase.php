<?php

namespace Sharifuddin\LaravelAiBridge\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Sharifuddin\LaravelAiBridge\AiBridgeServiceProvider;
use Sharifuddin\LaravelAiBridge\Contracts\EmbeddingProviderInterface;
use Sharifuddin\LaravelAiBridge\Contracts\ToolInterface;
use Sharifuddin\LaravelAiBridge\Contracts\ToolRegistryInterface;
use Sharifuddin\LaravelAiBridge\Contracts\VectorStoreInterface;
use Sharifuddin\LaravelAiBridge\DTO\VectorRecord;
use Sharifuddin\LaravelAiBridge\Tests\Fakes\FakeEmbeddingProvider;
use Sharifuddin\LaravelAiBridge\VectorStore\ArrayVectorStore;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            AiBridgeServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('ai-bridge.gemini.key', 'test-gemini-key');
        $app['config']->set('ai-bridge.gemini.model', 'gemini-1.5-flash');
        $app['config']->set('cache.default', 'array');

        // Deterministic, dependency-free defaults for the whole suite;
        // individual tests override bindings/config as needed.
        $app['config']->set('ai-bridge.embedding.driver', 'hash');
        $app['config']->set('ai-bridge.vector.driver', 'array');
        $app['config']->set('ai-bridge.legacy_controllers.enabled', false);

        // Optional live database connections, used only by the
        // PostgreSQLVectorStore/MySQLVectorStore integration tests (which
        // skip themselves gracefully when these servers aren't reachable).
        $app['config']->set('database.connections.pgsql_test', [
            'driver' => 'pgsql',
            'host' => env('AI_BRIDGE_TEST_PG_HOST', '127.0.0.1'),
            'port' => env('AI_BRIDGE_TEST_PG_PORT', '5432'),
            'database' => env('AI_BRIDGE_TEST_PG_DATABASE', 'ai_bridge_test'),
            'username' => env('AI_BRIDGE_TEST_PG_USERNAME', 'postgres'),
            'password' => env('AI_BRIDGE_TEST_PG_PASSWORD', 'postgres'),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
        ]);

        $app['config']->set('database.connections.mysql_test', [
            'driver' => 'mysql',
            'host' => env('AI_BRIDGE_TEST_MYSQL_HOST', '127.0.0.1'),
            'port' => env('AI_BRIDGE_TEST_MYSQL_PORT', '3306'),
            'database' => env('AI_BRIDGE_TEST_MYSQL_DATABASE', 'ai_bridge_test'),
            'username' => env('AI_BRIDGE_TEST_MYSQL_USERNAME', 'root'),
            'password' => env('AI_BRIDGE_TEST_MYSQL_PASSWORD', 'root'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]);
    }

    /**
     * Swaps in the deterministic FakeEmbeddingProvider (with the given
     * phrase => vector map) and a fresh ArrayVectorStore, so retrieval
     * tests get fully predictable similarity scores without real network
     * calls or hashing noise.
     *
     * @param array<string, array<int, float>> $vectors
     */
    protected function useFakeEmbeddings(array $vectors): FakeEmbeddingProvider
    {
        FakeEmbeddingProvider::$vectors = $vectors;

        // Start each test from a clean registry, retriever, and vector state to
        // avoid stale tool registrations or cached search results leaking across cases.
        $this->app->forgetInstance(ToolRegistryInterface::class);
        $this->app->forgetInstance(\Sharifuddin\LaravelAiBridge\Retrieval\ToolRetriever::class);
        $this->app->forgetInstance(\Sharifuddin\LaravelAiBridge\Services\AiService::class);
        $this->app->forgetInstance(\Sharifuddin\LaravelAiBridge\Execution\ToolExecutor::class);
        \Illuminate\Support\Facades\Cache::forget('ai-bridge:vector:array:index');
        \Illuminate\Support\Facades\Cache::flush();

        $provider = new FakeEmbeddingProvider();

        $this->app->extend(EmbeddingProviderInterface::class, fn () => $provider);
        $this->app->extend(VectorStoreInterface::class, fn () => new ArrayVectorStore());

        return $provider;
    }

    /**
     * Simulates `ai:tools:index` for a single tool in tests: computes its
     * embedding via the currently-bound EmbeddingProviderInterface and
     * upserts it into the currently-bound VectorStoreInterface, without
     * going through the ToolIndexer's hash-skip/caching logic.
     */
    protected function indexTool(ToolInterface $tool): void
    {
        $embeddings = $this->app->make(EmbeddingProviderInterface::class);
        $vectorStore = $this->app->make(VectorStoreInterface::class);

        $text = $tool->embeddingText();
        $vector = $embeddings->embed($text);

        $vectorStore->upsert(new VectorRecord($tool->name(), $vector, [], $text, sha1($text)));
    }
}
