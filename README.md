# Laravel AI Bridge — AI Agent, Function Calling & Vector Search Package for Laravel

**Laravel AI Bridge** is a production-grade **Laravel AI integration package** that turns any Laravel application into an **AI agent** capable of understanding natural-language prompts and safely executing your existing controllers, services, and business logic as callable "tools." It supports **Gemini, OpenAI (ChatGPT), DeepSeek, Anthropic (Claude), and any custom or self-hosted AI model**, with **automatic vector embedding generation**, **automatic AI provider failover**, and **zero-code tool discovery** — making it the fastest way to add a **Laravel AI chatbot**, **Laravel AI assistant**, or **natural-language-to-API layer** to any existing Laravel project.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sharifuddin/laravel-ai-bridge.svg?style=flat-square)](https://packagist.org/packages/sharifuddin/laravel-ai-bridge)
[![Total Downloads](https://img.shields.io/packagist/dt/sharifuddin/laravel-ai-bridge.svg?style=flat-square)](https://packagist.org/packages/sharifuddin/laravel-ai-bridge)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-%5E8.1%20%7C%20%5E8.2%20%7C%20%5E8.3%20%7C%20%5E8.4-blue.svg?style=flat-square)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-9.x%2B%20%7C%2010.x%20%7C%2011.x%20%7C%2012.x-orange)](https://laravel.com)
[![Vector Database](https://img.shields.io/badge/Vector%20DB-PostgreSQL%20%7C%20MySQL%20%7C%20MongoDB%20%7C%20Qdrant%20%7C%20Pinecone-6f42c1)](#-vector-database-support-postgresql-mysql-mongodb-qdrant-pinecone)

> **Keywords:** Laravel AI package, Laravel AI agent, Laravel function calling, Laravel Gemini integration, Laravel OpenAI integration, Laravel ChatGPT package, Laravel DeepSeek integration, Laravel vector database, Laravel MySQL vector search, Laravel pgvector, natural language to Laravel controller, Laravel AI chatbot, Laravel LLM tool calling, AI agent auto failover.

---

## 📚 Table of Contents

- [Why Laravel AI Bridge?](#-why-laravel-ai-bridge)
- [Feature Highlights](#-feature-highlights)
- [Feature Comparison Matrix](#-feature-comparison-matrix)
- [Requirements](#️-requirements)
- [Installation — 2-Command Setup](#-installation--2-command-setup)
- [Automatic AI Provider Switching & Failover](#-automatic-ai-provider-switching--failover-gemini-openai-deepseek-anthropic--custom)
- [Automatic Tool & Vector Generation](#-automatic-tool--vector-generation-zero-config-discovery)
- [Vector Database Support](#-vector-database-support-postgresql-mysql-mongodb-qdrant-pinecone)
- [File / Excel / PDF Export Support](#-file-excel-pdf-export-support)
- [Defining a First-Class AI Tool](#-defining-a-first-class-ai-tool)
- [Backward Compatibility (Legacy Controllers)](#-backward-compatibility-legacy-controller-tools)
- [Artisan Commands](#-artisan-commands)
- [Architecture](#-architecture)
- [Security Model](#-security-model)
- [Chat UI](#-chat-ui)
- [Frequently Asked Questions (FAQ)](#-frequently-asked-questions-faq)
- [Testing](#-testing)
- [License](#-license)
- [Author](#-author)
- [Quick Start Cheat Sheet](#-quick-start-cheat-sheet)

---

## 🔥 Why Laravel AI Bridge?

Most "AI for Laravel" tutorials wire up a single hardcoded prompt to a single hardcoded model. **Laravel AI Bridge is different** — it's a full **AI orchestration layer** built for real production apps with hundreds of existing controllers:

* 🧠 **Automatic Vector Data Generation** — every controller/tool is automatically embedded into a vector database. No manual embedding pipeline, no manual metadata entry.
* 🔁 **Auto AI Agent / Provider Switching** — if Gemini hits a rate limit, quota, or outage, the package **automatically fails over** to OpenAI, DeepSeek, Anthropic, or any custom model — with zero downtime and zero code changes.
* ⚡ **Just 2 Commands to Install & Run** — `composer require` + `php artisan ai:tools:index` and your Laravel app already has a working AI agent.
* 🗄 **Multi Vector Database Support** — PostgreSQL (pgvector), **MySQL**, MongoDB Atlas Vector Search, Qdrant, Pinecone, or a dependency-free in-memory store.
* 🔍 **Zero-Config Query & Filter Discovery** — pagination, search, date ranges, foreign-key filters, and bulk array fields are auto-detected straight from your controller code — no manual schema writing.
* 🔐 **Enterprise-Grade Security** — every AI-selected tool call is re-validated and re-authorized by Laravel's own Gate/Policy system before it ever runs.

---

## ✨ Feature Highlights

* **Hybrid AI tool retrieval** — query normalization → embedding → vector search → lexical scoring → permission/tenant filtering → ranked top-K → confidence/ambiguity detection. The AI model only ever sees the smallest useful set of tool declarations, never your whole tool catalog — keeping token cost and hallucination risk low.
* **Multi-provider AI agent switching** — Gemini, OpenAI (ChatGPT), DeepSeek, Anthropic (Claude), or any OpenAI-compatible custom/self-hosted model (Groq, OpenRouter, Ollama, vLLM, local LLMs) — swap providers with a single `.env` change.
* **Automatic provider failover** — configure an ordered fallback chain (e.g. Gemini → OpenAI → DeepSeek); on a rate limit, quota error, outage, timeout, or any provider error, the package automatically retries the next provider in the chain and logs/emits an event for observability. Zero silent failures.
* **Automatic controller & vector discovery** — point it at your existing controllers and it automatically discovers every `index`/`show`/`store`/`update`/`destroy` (and custom) action, generates a rich, filter-aware description for each, and embeds it into your vector database — no manual tool registration required.
* **Zero-config query parameter discovery** — pagination (`per_page`, `page`), search, sorting, date ranges (`from_date`/`to_date`), foreign-key filters (`*_id`), and export/format flags are automatically detected from real controller code, whether written as `$request->query()`, `$request->input()`, magic-property access (`$request->status`), `Validator::make()`, or a dedicated FormRequest class.
* **Bulk / array-of-objects support** — endpoints like `bulk_store`, `store_all`, or `bulk_update` that validate nested arrays (`items.*.name`, `items.*.quantity`) are fully understood and exposed to the AI with the correct nested object schema.
* **First-class `ToolInterface` tools** — define tools as small, testable classes mapped to your own Actions/Services, independent of any controller.
* **Full backward compatibility** — existing controllers keep working with zero changes, automatically flowing through the same secure retrieval + execution pipeline via a transparent adapter.
* **Any create/update coding style supported** — inline `$request->validate()`, dedicated FormRequest classes, Controller+Service architecture, or plain Eloquent `$fillable` mass-assignment — all four patterns are auto-detected generically, so it works regardless of each developer's personal coding style.
* **Route-model-binding aware** — `show(User $user)`, `update(User $user, Request $request)`, `destroy(User $user)` style controllers work out of the box; the AI only ever needs to supply a record ID.
* **File / binary export passthrough** — Excel, PDF, CSV, or any file-download response from your controller (`Excel::download()`, `response()->streamDownload()`, etc.) is streamed straight back to the client untouched, so "export this as Excel" prompts just work.
* **Security first** — every tool call is re-validated (types, `min`/`max`, `enum`, required fields) and re-authorized (`Gate`/`$user->can()`) immediately before execution, even though retrieval already filtered by permission. AI tool-call output is never trusted as proof of authorization.
* **Multi-tenant isolation** — tools can declare `requiresTenant()`; a pluggable `TenantContextResolverInterface` lets your app supply the current company/branch/organization without the package assuming any specific tenancy package.
* **Pluggable vector store** — PostgreSQL + pgvector by default, plus MySQL, MongoDB, Qdrant, and Pinecone as drop-in alternatives, with a dependency-free in-memory/cache-backed fallback so the package always works out of the box.
* **Token-efficient caching** — embeddings, retrieval results, and tool metadata are all cached via Laravel's cache abstraction, with keys safely namespaced by user/tenant so nothing leaks across accounts.
* **Bounded, normalized results** — tool output is truncated and shaped into `{success, data, meta}` before it ever reaches the AI or the HTTP response; large result sets never blow the context window.
* **Ready-to-use dual interfaces** — `POST /api/ai/prompt` (REST API for mobile/frontend apps) and `GET /ai/assistant` (Copilot-style chat UI) out of the box.

---

## 📊 Feature Comparison Matrix

| Capability | Laravel AI Bridge |
|---|---|
| Multi AI provider support (Gemini / OpenAI / DeepSeek / Anthropic / Custom) | ✅ |
| Automatic AI provider failover on rate limit / error | ✅ |
| Automatic vector embedding generation | ✅ |
| MySQL vector database support | ✅ |
| PostgreSQL (pgvector) vector database support | ✅ |
| MongoDB / Qdrant / Pinecone vector database support | ✅ |
| Zero-config controller discovery | ✅ |
| Auto pagination/filter/date-range parameter discovery | ✅ |
| FormRequest / Controller+Service / `$fillable` auto-detection | ✅ |
| Bulk array-of-objects (bulk create/update) support | ✅ |
| Excel / PDF / CSV export passthrough | ✅ |
| Permission & Gate re-authorization on every AI action | ✅ |
| Multi-tenant isolation | ✅ |
| Ready-made Chat UI | ✅ |
| 2-command installation | ✅ |

---

## ⚙️ Requirements

* **PHP:** `^8.1 | ^8.2 | ^8.3 | ^8.4`
* **Laravel:** `^9.0 | ^10.0 | ^11.0 | ^12.0`
* **An AI provider API key** — [Google Gemini](https://aistudio.google.com/), [OpenAI](https://platform.openai.com/), [DeepSeek](https://platform.deepseek.com/), or [Anthropic](https://console.anthropic.com/) (any one is enough to get started; add more for automatic failover)
* *(Default vector store)*: PostgreSQL with the [`pgvector`](https://github.com/pgvector/pgvector) extension (`CREATE EXTENSION vector;`) — the store bootstraps its own table automatically on first use.
* *(Optional alternatives)*: **MySQL** (stock MySQL/MariaDB, no extension needed), MongoDB (requires `composer require mongodb/mongodb` + `ext-mongodb` + an Atlas Vector Search index), Qdrant/Pinecone (called over HTTP, no extra composer dependency). If the selected driver's dependencies/connection aren't available, the package automatically falls back to the built-in dependency-free `array` driver.

---

## 📦 Installation — 2-Command Setup

Get a fully working Laravel AI agent running in just two commands:

```bash
# 1. Install the package
composer require sharifuddin/laravel-ai-bridge

# 2. Generate the vector index for every controller/tool automatically
php artisan ai:tools:index
```

That's it — add one AI provider API key to your `.env` and your Laravel app already understands natural-language prompts.

### Full setup (recommended for production)

**1. Install via Composer**

```bash
composer require sharifuddin/laravel-ai-bridge
```

**2. Publish configuration**

```bash
php artisan vendor:publish --tag=ai-bridge-config
```

**3. Configure your environment**

```env
# --- MySQL setup ---
GEMINI_API_KEY=your-gemini-api-key
AI_BRIDGE_EMBEDDING_DRIVER=gemini
AI_BRIDGE_EMBEDDING_MODEL=gemini-embedding-001

# Vector store — switch drivers with a single env var:
# AI_VECTOR_STORE=postgresql   # default
AI_VECTOR_STORE=mysql
```

```env
# --- PostgreSQL setup (default) ---
GEMINI_API_KEY=your-gemini-api-key
AI_BRIDGE_EMBEDDING_DRIVER=gemini

AI_VECTOR_STORE=postgresql   # default
# AI_VECTOR_STORE=mysql
# AI_VECTOR_STORE=mongodb
# AI_VECTOR_STORE=qdrant
# AI_VECTOR_STORE=pinecone
# AI_VECTOR_STORE=array      # dependency-free, local dev/testing only
```

```env
# --- Optional: automatic multi-provider failover ---
AI_BRIDGE_PROVIDER=gemini
OPENAI_API_KEY=your-openai-api-key
DEEPSEEK_API_KEY=your-deepseek-api-key
AI_BRIDGE_FAILOVER_ENABLED=true
AI_BRIDGE_FAILOVER_ORDER=openai,deepseek
```

**4. Generate the vector index**

```bash
php artisan ai:tools:index
```

**5. Start chatting**

```
POST /api/ai/prompt
{"prompt": "Show me all active users"}
```

or open the ready-made UI at `GET /ai/assistant`.

### Vector Store Architecture

```
VectorStoreInterface
        │
        ├── PostgreSQLVectorStore   ← default (pgvector)
        ├── MySQLVectorStore        ← zero-extension, works on stock MySQL/MariaDB
        ├── MongoVectorStore        ← MongoDB Atlas Vector Search
        ├── QdrantVectorStore
        ├── PineconeVectorStore
        └── ArrayVectorStore        ← dependency-free fallback
```

---

## 🔁 Automatic AI Provider Switching & Failover (Gemini, OpenAI, DeepSeek, Anthropic + Custom)

Laravel AI Bridge is not locked into a single AI vendor. Switch your **Laravel AI agent's** underlying model with a single `.env` change:

```env
AI_BRIDGE_PROVIDER=gemini      # default — Google Gemini
AI_BRIDGE_PROVIDER=openai      # OpenAI / ChatGPT
AI_BRIDGE_PROVIDER=deepseek    # DeepSeek
AI_BRIDGE_PROVIDER=anthropic   # Anthropic Claude
AI_BRIDGE_PROVIDER=my_custom   # any OpenAI-compatible or fully custom API — see config/ai-bridge.php
```

### 🚦 Auto Switch AI Agent if Limit Expired or Error Occurs

Configure an ordered **automatic failover chain**. If your primary provider hits a rate limit, a quota/billing error, a server outage, a network timeout, or any other error, Laravel AI Bridge **automatically and transparently switches to the next configured provider** — the end user never notices a thing:

```env
AI_BRIDGE_PROVIDER=gemini
AI_BRIDGE_FAILOVER_ENABLED=true
AI_BRIDGE_FAILOVER_ORDER=openai,deepseek
```

With the above, the chain is: **Gemini → OpenAI → DeepSeek**. If every provider in the chain fails, a single clear, combined error is returned (never a silent failure), and an `AiProviderFailedOver` event fires on every switch so you can log, alert, or track it in your own monitoring stack.

Adding a brand-new custom AI provider (a self-hosted model, Groq, OpenRouter, or any other OpenAI-compatible endpoint) is a **config-only change** — no PHP code required:

```php
// config/ai-bridge.php
'providers' => [
    'my_custom_model' => [
        'base_url' => env('MY_MODEL_URL'),
        'key' => env('MY_MODEL_KEY'),
        'model' => 'my-model-name',
    ],
],
```

---

## 🧠 Automatic Tool & Vector Generation (Zero-Config Discovery)

Laravel AI Bridge automatically scans your existing controllers and **generates rich, filter-aware vector embeddings** for every action — no manual metadata entry, no manual embedding pipeline:

* Every `index`, `show`, `store`, `update`, `destroy`, and custom controller method is discovered automatically and turned into an AI-callable tool.
* Pagination parameters (`per_page`, `page`), search terms, sort fields, date ranges (`from_date`/`to_date`), status filters, and foreign-key filters (`category_id`, `location_id`, ...) are **automatically detected** directly from your real controller code — however you wrote it:
  * `$request->query('per_page', 10)` / `$request->input(...)` / `$request->get(...)`
  * Laravel's "magic property" shortcut: `$request->status`
  * `$request->validate([...])`, a dedicated **FormRequest** class, or a standalone `Validator::make(...)` call
  * Plain Eloquent `$fillable` mass-assignment, as a smart fallback when no explicit validation exists
* **Bulk/array endpoints** (`bulk_store`, `store_all`, `bulk_update`) with nested validation like `'items.*.name' => 'required|string'` are understood as true array-of-objects schemas, not flattened or misdetected.
* **Route-model-binding** controllers (`show(User $user)`) are fully supported — the AI is told exactly which ID to supply, and the real Eloquent model is resolved automatically at execution time.
* Regenerate the entire vector index at any time with **zero downtime** — existing embeddings keep serving live traffic while new ones are generated and swapped in row by row:

```bash
php artisan ai:tools:index      # index new/changed tools only (fast, incremental)
php artisan ai:tools:reindex    # force full re-embedding of everything
```

---

## 🗄 Vector Database Support (PostgreSQL, MySQL, MongoDB, Qdrant, Pinecone)

Laravel AI Bridge ships with a **pluggable vector database layer** — pick whichever your infrastructure already runs, with zero code changes:

| Driver | Extension Required | Best For |
|---|---|---|
| `postgresql` (default) | [`pgvector`](https://github.com/pgvector/pgvector) | Teams already on Postgres wanting native vector search |
| **`mysql`** | **None — works on stock MySQL/MariaDB** | Teams who want vector search **without installing any new database or extension** |
| `mongodb` | `mongodb/mongodb` + Atlas Vector Search | MongoDB-based applications |
| `qdrant` | None (HTTP API) | Dedicated, high-performance vector search infrastructure |
| `pinecone` | None (HTTP API) | Fully managed vector search SaaS |
| `array` | None | Local development / testing, dependency-free |

Switch between them with a single environment variable:

```env
AI_VECTOR_STORE=mysql
```

The **MySQL vector store** is especially popular for teams who want AI-powered semantic search over their Laravel controllers **without provisioning a new database engine** — it stores embeddings directly in a MySQL table and performs similarity ranking without any external service.

---

## 📁 File / Excel / PDF Export Support

Ask your AI agent to export data and get back a real, working file — not broken JSON. Any controller action that returns a file download (`Excel::download()`, `response()->streamDownload()`, `Storage::download()`, or any `Response` with a `Content-Disposition: attachment` header) is detected automatically and **streamed straight through to the HTTP response untouched**:

```
POST /api/ai/prompt
{"prompt": "Export all packagings from 2026-09-01 to 2026-09-13 as Excel"}
```

No special configuration needed — this works automatically alongside the same auto-discovered `export=excel`-style query parameters described above.

---

## 🛠 Defining a First-Class AI Tool

```php
namespace App\AiTools;

use Sharifuddin\LaravelAiBridge\Execution\ExecutionContext;
use Sharifuddin\LaravelAiBridge\Tools\AbstractTool;

class ListUsersTool extends AbstractTool
{
    public function name(): string { return 'list_users'; }

    public function category(): string { return 'users'; }

    public function description(): string
    {
        return 'List application users with pagination, search, status filtering and role filtering.';
    }

    public function parametersSchema(): array
    {
        return [
            'search'   => ['type' => 'string', 'required' => false, 'description' => 'Search by name or email'],
            'status'   => ['type' => 'string', 'required' => false, 'enum' => ['active', 'inactive']],
            'per_page' => ['type' => 'integer', 'required' => false, 'default' => 20, 'min' => 1, 'max' => 100],
        ];
    }

    public function permission(): ?string { return 'view-users'; }

    public function metadata(): array
    {
        return ['aliases' => ['users list', 'user list'], 'entity' => 'user', 'action' => 'list'];
    }

    public function execute(array $arguments, ExecutionContext $context): mixed
    {
        return app(\App\Services\UserService::class)->list($arguments, $context);
    }
}
```

Register it (e.g. in a service provider's `boot()`, or list it in `config/ai-bridge.php`):

```php
use Sharifuddin\LaravelAiBridge\Facades\AI;

AI::tool(\App\AiTools\ListUsersTool::class);
```

### Custom tool example for your own app

This is the pattern most apps use when they want AI to call their own business logic:

```php
<?php

namespace App\AiTools;

use Sharifuddin\LaravelAiBridge\Execution\ExecutionContext;
use Sharifuddin\LaravelAiBridge\Tools\AbstractTool;

class WeatherTool extends AbstractTool
{
    public function name(): string
    {
        return 'get_weather';
    }

    public function category(): string
    {
        return 'weather';
    }

    public function description(): string
    {
        return 'Get the current weather for a city using the app weather service.';
    }

    public function parametersSchema(): array
    {
        return [
            'city' => [
                'type' => 'string',
                'required' => true,
                'description' => 'City name, for example Dhaka or London',
            ],
            'unit' => [
                'type' => 'string',
                'required' => false,
                'enum' => ['celsius', 'fahrenheit'],
                'default' => 'celsius',
            ],
        ];
    }

    public function permission(): ?string
    {
        return null; // or 'view-weather' if you want to require authorization
    }

    public function metadata(): array
    {
        return [
            'aliases' => ['weather report', 'current weather', 'forecast'],
            'entity' => 'weather',
            'action' => 'lookup',
        ];
    }

    public function execute(array $arguments, ExecutionContext $context): mixed
    {
        $city = $arguments['city'];
        $unit = $arguments['unit'] ?? 'celsius';

        return app(\App\Services\WeatherService::class)->getForCity($city, $unit);
    }
}
```

And register it in a service provider:

```php
use Sharifuddin\LaravelAiBridge\Facades\AI;

public function boot(): void
{
    AI::tool(\App\AiTools\WeatherTool::class);
}
```

Then index and ask the assistant:

```bash
php artisan ai:tools:index
```

```json
POST /api/ai/prompt
{
  "prompt": "What is the weather in Dhaka today?"
}
```

The AI will only see the declarations for the tools that match the prompt, and it will execute the selected custom tool only after validation + re-authorization.

Behind the scenes: normalize → embed → vector search → hybrid rank → permission/tenant filter → send only the matching tool declarations to the active AI provider → validate its chosen arguments → re-authorize → execute → return a compact, bounded JSON result (or stream a file, for exports).

### Multi-tenant tools

```php
public function requiresTenant(): bool { return true; }
```

```php
// AppServiceProvider::register()
$this->app->bind(TenantContextResolverInterface::class, fn () =>
    new CallbackTenantContextResolver(fn () => auth()->user()
        ? ['company_id' => auth()->user()->company_id]
        : null
    )
);
```

---

## 🧩 Backward Compatibility (Legacy Controller Tools)

Controllers previously discovered via `ToolProviderInterface`/`CachedControllerToolProvider` (implementing the interface, or just reflected by public method) keep working with **zero changes**. They are wrapped as `LegacyControllerToolAdapter` instances and merged into the same registry, so they get the same semantic retrieval, argument validation, and authorization re-check as first-class tools — plus automatic query-parameter, FormRequest, bulk-array, and route-model-binding discovery described above. Disable this via `ai-bridge.legacy_controllers.enabled = false` once fully migrated.

The original `AiProcessorInterface` methods (`processPrompt()`, `selectRelevantTools()`) are preserved unchanged for any code calling them directly. `AiController` and the new `AiService::chat()` pipeline are what changed.

---

## ⚡ Artisan Commands

```bash
php artisan ai:tools:index                 # index new/changed tools (skips unchanged embeddings)
php artisan ai:tools:reindex                # force re-embed everything, zero downtime
php artisan ai:tools:reindex --tool=list_users
php artisan ai:tools:list                   # inspect the currently registered tools
```

---

## 🗂 Architecture

```
User → ChatRequest → QueryNormalizer → EmbeddingProvider → VectorStore
     → (semantic + lexical + metadata) hybrid scoring → permission/tenant filter
     → ranked top-K → confidence/ambiguity check → AiProviderManager
     → (Gemini | OpenAI | DeepSeek | Anthropic | Custom, with automatic failover)
     → ArgumentValidator → re-authorization → ToolExecutor → your Action/Service
     → ToolResult (normalized, truncated, or a streamed file) → AI → final response
```

Key interfaces (all in `Sharifuddin\LaravelAiBridge\Contracts`): `ToolInterface`, `ToolRegistryInterface`, `VectorStoreInterface`, `EmbeddingProviderInterface`, `AiProviderInterface`, `TenantContextResolverInterface`. Swap any implementation without touching the rest of the pipeline.

See `config/ai-bridge.php` for every tunable: retrieval weights/thresholds, cache TTLs, vector store connection, embedding driver, provider failover order, execution limits.

---

## 🔐 Security Model

* AI never decides authorization — Laravel does, twice: once at retrieval (so disallowed tools are never even shown to the model) and again immediately before execution.
* Arguments are whitelisted and validated against each tool's declared schema (types, `min`/`max`, `enum`, required fields) — an AI-supplied `per_page=999999` is rejected, not clamped silently.
* Only tools registered in the `ToolRegistry` can ever be executed — there is no arbitrary class/method invocation from AI-generated strings.
* Cache keys fold in a security fingerprint (user + tenant), so retrieval/embedding caches can never serve one user's/tenant's results to another.
* API keys and credentials are always read from your own `.env` — the package never logs, stores, or transmits them anywhere outside your configured AI provider requests.

---

## 💬 Chat UI

A ready-made, Copilot-style chat interface is included out of the box at `GET /ai/assistant` — no frontend build step required.

![Laravel AI Bridge chat UI showing a conversation with the AI agent](image-4.png)

![Laravel AI Bridge chat UI displaying a tool-call result as a formatted table](image-3.png)

![Laravel AI Bridge chat UI on mobile view](image-5.png)

![Laravel AI Bridge chat UI with pinned prompts sidebar](<Screenshot from 2026-09-11 16-23-20.png>)

---

## ❓ Frequently Asked Questions (FAQ)

**Does Laravel AI Bridge work with OpenAI/ChatGPT instead of Gemini?**
Yes. Set `AI_BRIDGE_PROVIDER=openai` and add your `OPENAI_API_KEY` — no code changes needed. DeepSeek and Anthropic (Claude) are supported the same way.

**What happens if my AI provider's API key runs out of quota?**
If you've configured `AI_BRIDGE_FAILOVER_ORDER`, Laravel AI Bridge automatically switches to the next configured AI provider with zero downtime and zero code changes.

**Can I use MySQL instead of PostgreSQL for vector search?**
Yes. Set `AI_VECTOR_STORE=mysql` — no extensions or additional services required, it works on stock MySQL/MariaDB.

**Do I need to manually register every controller as an AI tool?**
No. Existing controllers are discovered automatically, including their query parameters, filters, and validation rules — you only need to define a first-class `ToolInterface` tool if you want to expose logic that isn't already behind a controller.

**Does it support bulk create/update endpoints?**
Yes. Endpoints validating arrays of objects (e.g. `'items.*.name' => 'required|string'`) are automatically understood and exposed to the AI with the correct nested schema.

**Can the AI export data as an Excel or PDF file?**
Yes. Any controller action returning a file download response is streamed back to the client automatically — no extra configuration needed.

**Is it safe to let an AI model call my application's controllers directly?**
Yes — every AI-selected tool call is re-validated against a strict argument schema and re-authorized through Laravel's own Gate/Policy system immediately before execution, regardless of what the AI model returns.

---

## 🧪 Testing

```bash
composer install
vendor/bin/phpunit
```

---

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 👨‍💻 Author

**Sharif Uddin**

- GitHub: [@sharifwebdev](https://github.com/sharifwebdev)
- Email: sharif.webpro@gmail.com
- Website: [https://sharifwebdev.github.io/](https://sharifwebdev.github.io/)

---

## 🌟 Support

If you find this Laravel AI package useful, please consider:

- ⭐ Starring the repository
- 🐛 Reporting issues
- 💡 Suggesting features
- 🔧 Submitting pull requests

---

## 📚 Changelog

Detailed changes for each release are documented in [CHANGES.md](CHANGES.md).

---

## 🔗 Links

- [Packagist](https://packagist.org/packages/sharifuddin/laravel-ai-bridge)
- [GitHub Repository](https://github.com/sharifuddin/laravel-ai-bridge)
- [Issue Tracker](https://github.com/sharifuddin/laravel-ai-bridge/issues)
- [Documentation](https://github.com/sharifuddin/laravel-ai-bridge/wiki)

---

## 🎯 Quick Start Cheat Sheet

```bash
# 1. Install (command 1 of 2)
composer require sharifuddin/laravel-ai-bridge

# 2. Index your controllers into the vector database automatically (command 2 of 2)
php artisan ai:tools:index

# Add at least one AI provider key to .env
GEMINI_API_KEY=your-gemini-api-key
AI_BRIDGE_EMBEDDING_DRIVER=gemini
AI_VECTOR_STORE=postgresql   # or: mysql / mongodb / qdrant / pinecone / array

# Optional: enable automatic multi-provider failover
AI_BRIDGE_FAILOVER_ENABLED=true
AI_BRIDGE_FAILOVER_ORDER=openai,deepseek

# Chat via API
POST /api/ai/prompt
{"prompt": "Show me all active users"}

# OR use the ready-made Chat UI
GET /ai/assistant
```

---

**Laravel AI Bridge** — the fastest way to turn any Laravel application into a secure, production-ready **AI agent**. 🤖✨