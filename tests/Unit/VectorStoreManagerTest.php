<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Unit;

use Sharifuddin\LaravelAiBridge\Exceptions\VectorStoreException;
use Sharifuddin\LaravelAiBridge\Tests\TestCase;
use Sharifuddin\LaravelAiBridge\VectorStore\ArrayVectorStore;
use Sharifuddin\LaravelAiBridge\VectorStore\VectorStoreManager;

class VectorStoreManagerTest extends TestCase
{
    public function test_defaults_to_postgresql_driver_name(): void
    {
        $default = require __DIR__ . '/../../config/ai-bridge.php';

        $this->assertSame('postgresql', $default['vector']['driver']);
    }

    public function test_falls_back_to_array_store_when_postgresql_driver_unavailable(): void
    {
        // Testbench's default DB connection is SQLite in-memory, not
        // PostgreSQL, so selecting "postgresql" must gracefully degrade
        // to the array store rather than throwing, per the package's
        // fallback strategy for vector-store unavailability.
        config(['ai-bridge.vector.driver' => 'postgresql', 'ai-bridge.vector.fallback_to_array' => true]);

        $store = VectorStoreManager::resolve();

        $this->assertInstanceOf(ArrayVectorStore::class, $store);
    }

    public function test_falls_back_to_array_store_when_mysql_driver_unavailable(): void
    {
        config(['ai-bridge.vector.driver' => 'mysql', 'ai-bridge.vector.fallback_to_array' => true]);

        $this->assertInstanceOf(ArrayVectorStore::class, VectorStoreManager::resolve());
    }

    public function test_falls_back_to_array_store_when_mongodb_driver_unavailable(): void
    {
        config(['ai-bridge.vector.driver' => 'mongodb', 'ai-bridge.vector.fallback_to_array' => true]);

        $this->assertInstanceOf(ArrayVectorStore::class, VectorStoreManager::resolve());
    }

    public function test_falls_back_to_array_store_when_qdrant_unreachable(): void
    {
        config([
            'ai-bridge.vector.driver' => 'qdrant',
            'ai-bridge.vector.qdrant.base_url' => 'http://127.0.0.1:1', // nothing listening
            'ai-bridge.vector.fallback_to_array' => true,
        ]);

        $this->assertInstanceOf(ArrayVectorStore::class, VectorStoreManager::resolve());
    }

    public function test_falls_back_to_array_store_when_pinecone_not_configured(): void
    {
        config([
            'ai-bridge.vector.driver' => 'pinecone',
            'ai-bridge.vector.pinecone.host' => '',
            'ai-bridge.vector.pinecone.api_key' => '',
            'ai-bridge.vector.fallback_to_array' => true,
        ]);

        $this->assertInstanceOf(ArrayVectorStore::class, VectorStoreManager::resolve());
    }

    public function test_throws_when_driver_unavailable_and_fallback_disabled(): void
    {
        config(['ai-bridge.vector.driver' => 'postgresql', 'ai-bridge.vector.fallback_to_array' => false]);

        $this->expectException(VectorStoreException::class);
        VectorStoreManager::resolve();
    }

    public function test_array_driver_resolves_directly(): void
    {
        config(['ai-bridge.vector.driver' => 'array']);

        $this->assertInstanceOf(ArrayVectorStore::class, VectorStoreManager::resolve());
    }

    public function test_unknown_driver_falls_back_or_throws_cleanly(): void
    {
        config(['ai-bridge.vector.driver' => 'not-a-real-driver', 'ai-bridge.vector.fallback_to_array' => false]);

        $this->expectException(VectorStoreException::class);
        VectorStoreManager::resolve();
    }
}
