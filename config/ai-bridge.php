<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Provider
    |--------------------------------------------------------------------------
    |
    | Which chat/generation provider to use. "gemini" ships fully
    | implemented; add further drivers via AiProviderManager.
    |
    */
    'provider' => env('AI_BRIDGE_PROVIDER', 'gemini'),

    'gemini' => [
        'key' => env('GEMINI_API_KEY', ''),
        'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),
        'api_url' => env('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models'),
    ],

    /*
    |--------------------------------------------------------------------------
    | First-Class AI Tools
    |--------------------------------------------------------------------------
    |
    | List ToolInterface class names to register at boot, e.g.:
    |   \App\AiTools\ListUsersTool::class,
    |   \App\AiTools\GetOrderTool::class,
    |
    | Tools can also be registered at runtime via AI::tool(...).
    |
    */
    'tools' => [
        // \App\AiTools\ListUsersTool::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Controller Reflection (Backward Compatibility)
    |--------------------------------------------------------------------------
    |
    | When enabled, controllers configured below (or auto-discovered under
    | app/Http/Controllers) are still reflected into tools, wrapped as
    | LegacyControllerToolAdapter, and flow through the exact same
    | retrieval/validation/authorization/execution pipeline as first-class
    | tools. Disable once you've migrated fully to ToolInterface classes.
    |
    */
    'legacy_controllers' => [
        'enabled' => env('AI_BRIDGE_LEGACY_CONTROLLERS_ENABLED', true),
        'enforce_permissions' => env('AI_BRIDGE_LEGACY_ENFORCE_PERMISSIONS', true),
    ],

    'controllers' => [
        // Example: \App\Http\Controllers\UserController::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Embeddings
    |--------------------------------------------------------------------------
    |
    | driver: "gemini" (production, requires GEMINI_API_KEY) or "hash"
    | (deterministic, dependency-free - local dev/tests only, NOT
    | semantically meaningful).
    |
    */
    'embedding' => [
        'driver' => env('AI_BRIDGE_EMBEDDING_DRIVER', 'gemini'),
        'gemini_model' => env('AI_BRIDGE_EMBEDDING_MODEL', 'gemini-embedding-001'),
        'dimensions' => (int) env('AI_BRIDGE_EMBEDDING_DIMENSIONS', 768),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vector Store
    |--------------------------------------------------------------------------
    |
    | driver: "postgresql" (default - requires the pgvector Postgres
    | extension), "mysql" (JSON-column + application-level cosine
    | similarity - works on stock MySQL, no extension required), "mongodb"
    | (MongoDB Atlas Vector Search - requires mongodb/mongodb + ext-mongodb),
    | "qdrant" or "pinecone" (external vector databases, called over their
    | HTTP APIs), or "array" (dependency-free, cache-backed - local
    | dev/testing only). If the selected driver's dependencies/connection
    | are unavailable, the manager automatically falls back to "array"
    | unless fallback_to_array is set to false.
    |
    | AI_VECTOR_STORE=postgresql   (default)
    | AI_VECTOR_STORE=mysql
    | AI_VECTOR_STORE=mongodb
    | AI_VECTOR_STORE=qdrant
    | AI_VECTOR_STORE=pinecone
    | AI_VECTOR_STORE=array
    |
    */
    'vector' => [
        // AI_BRIDGE_VECTOR_DRIVER is kept as a deprecated fallback for
        // anyone who already set it before AI_VECTOR_STORE existed.
        'driver' => env('AI_VECTOR_STORE', env('AI_BRIDGE_VECTOR_DRIVER', 'postgresql')),
        'fallback_to_array' => env('AI_BRIDGE_VECTOR_FALLBACK_TO_ARRAY', true),

        'postgresql' => [
            // Null = reuse the host app's default Laravel DB connection
            // (config('database.default')), which must point at a
            // Postgres database with the `pgvector` extension available.
            'connection' => env('AI_VECTOR_PG_CONNECTION'),
            'table' => env('AI_VECTOR_PG_TABLE', 'ai_tool_vectors'),
            'dimensions' => (int) env('AI_BRIDGE_EMBEDDING_DIMENSIONS', 768),
            // "vector_cosine_ops" (default), "vector_l2_ops", "vector_ip_ops"
            'distance' => env('AI_VECTOR_PG_DISTANCE', 'cosine'),
        ],

        'mysql' => [
            'connection' => env('AI_VECTOR_MYSQL_CONNECTION'),
            'table' => env('AI_VECTOR_MYSQL_TABLE', 'ai_tool_vectors'),
        ],

        'mongodb' => [
            'uri' => env('AI_VECTOR_MONGO_URI', env('AI_BRIDGE_MONGO_URI', 'mongodb://127.0.0.1:27017')),
            'database' => env('AI_VECTOR_MONGO_DATABASE', env('AI_BRIDGE_MONGO_DATABASE', 'ai_bridge')),
            'collection' => env('AI_VECTOR_MONGO_COLLECTION', env('AI_BRIDGE_MONGO_COLLECTION', 'ai_tool_vectors')),
            'index_name' => env('AI_VECTOR_MONGO_INDEX_NAME', env('AI_BRIDGE_MONGO_INDEX_NAME', 'ai_tool_vector_index')),
            'similarity' => env('AI_VECTOR_MONGO_SIMILARITY', env('AI_BRIDGE_MONGO_SIMILARITY', 'cosine')),
        ],

        'qdrant' => [
            'base_url' => env('AI_VECTOR_QDRANT_URL', 'http://127.0.0.1:6333'),
            'api_key' => env('AI_VECTOR_QDRANT_API_KEY'),
            'collection' => env('AI_VECTOR_QDRANT_COLLECTION', 'ai_tool_vectors'),
            'dimensions' => (int) env('AI_BRIDGE_EMBEDDING_DIMENSIONS', 768),
            'distance' => env('AI_VECTOR_QDRANT_DISTANCE', 'Cosine'),
        ],

        'pinecone' => [
            'api_key' => env('AI_VECTOR_PINECONE_API_KEY'),
            // Pinecone index host, e.g. https://ai-tool-vectors-xxxx.svc.us-east-1-aws.pinecone.io
            'host' => env('AI_VECTOR_PINECONE_HOST'),
            'namespace' => env('AI_VECTOR_PINECONE_NAMESPACE', 'ai-bridge-tools'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retrieval / Ranking

    |--------------------------------------------------------------------------
    |
    | Hybrid scoring: final_score = semantic*weights.semantic +
    | lexical*weights.lexical + metadata*weights.metadata. top_k tools (at
    | most max_candidates) are sent to the AI model, after filtering out
    | anything below min_similarity. confidence_threshold/score_margin
    | drive ambiguity detection.
    |
    */
    'retrieval' => [
        'vector_top_k' => (int) env('AI_BRIDGE_VECTOR_TOP_K', 20),
        'top_k' => (int) env('AI_BRIDGE_RETRIEVAL_TOP_K', 5),
        'max_candidates' => (int) env('AI_BRIDGE_RETRIEVAL_MAX_CANDIDATES', 10),
        'min_similarity' => (float) env('AI_BRIDGE_RETRIEVAL_MIN_SIMILARITY', 0.15),
        'confidence_threshold' => (float) env('AI_BRIDGE_RETRIEVAL_CONFIDENCE_THRESHOLD', 0.55),
        'score_margin' => (float) env('AI_BRIDGE_RETRIEVAL_SCORE_MARGIN', 0.08),
        'weights' => [
            'semantic' => (float) env('AI_BRIDGE_WEIGHT_SEMANTIC', 0.6),
            'lexical' => (float) env('AI_BRIDGE_WEIGHT_LEXICAL', 0.3),
            'metadata' => (float) env('AI_BRIDGE_WEIGHT_METADATA', 0.1),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Execution
    |--------------------------------------------------------------------------
    */
    'execution' => [
        // Maximum records returned to the AI/HTTP response for any single
        // tool call - large result sets are truncated, not sent in full.
        'max_result_records' => (int) env('AI_BRIDGE_MAX_RESULT_RECORDS', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Uses Laravel's cache abstraction (any driver - Redis recommended in
    | production). Cache keys always fold in a security fingerprint
    | (user + tenant) so results never leak across users/tenants.
    |
    */
    'cache' => [
        'enabled' => env('AI_BRIDGE_CACHE_ENABLED', true),
        // Null = default cache store from config('cache.default').
        'store' => env('AI_BRIDGE_CACHE_STORE'),
        // Legacy single key/ttl, still used by CachedControllerToolProvider.
        'key' => env('AI_BRIDGE_CACHE_KEY', 'ai_controller_tools'),
        'ttl' => (int) env('AI_BRIDGE_CACHE_TTL', 3600),
        'ttls' => [
            'embedding' => (int) env('AI_BRIDGE_CACHE_TTL_EMBEDDING', 604800), // 7 days
            'retrieval' => (int) env('AI_BRIDGE_CACHE_TTL_RETRIEVAL', 300),    // 5 minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Routes Configuration
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'enabled' => env('AI_BRIDGE_ROUTES_ENABLED', true),
        'api_prefix' => env('AI_BRIDGE_API_PREFIX', 'api/ai'),
        'web_prefix' => env('AI_BRIDGE_WEB_PREFIX', 'ai'),
        'api_middleware' => ['api'],
        'web_middleware' => ['web'],
    ],
];
