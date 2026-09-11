<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Sharifuddin\LaravelAiBridge\Contracts\AiProviderInterface;
use Sharifuddin\LaravelAiBridge\Contracts\ToolRegistryInterface;
use Sharifuddin\LaravelAiBridge\DTO\AiResponse;
use Sharifuddin\LaravelAiBridge\Providers\GeminiAiProvider;
use Sharifuddin\LaravelAiBridge\Services\AiService;
use Sharifuddin\LaravelAiBridge\Tests\Fakes\FakeAiProvider;
use Sharifuddin\LaravelAiBridge\Tests\Fakes\GetOrderTool;
use Sharifuddin\LaravelAiBridge\Tests\Fakes\ListUsersTool;
use Sharifuddin\LaravelAiBridge\Tests\TestCase;

class ChatPipelineTest extends TestCase
{
    private function fakeUser(array $abilities = []): object
    {
        return new class ($abilities) {
            public function __construct(private array $abilities)
            {
            }

            public function getAuthIdentifier(): int
            {
                return 1;
            }

            public function can($ability): bool
            {
                return in_array($ability, $this->abilities, true);
            }
        };
    }

    private function bindFakeAiProvider(): void
    {
        $this->app->extend(AiProviderInterface::class, fn () => new FakeAiProvider());
    }

    public function test_chat_returns_plain_text_when_ai_does_not_select_a_tool(): void
    {
        $this->useFakeEmbeddings([]);
        $this->bindFakeAiProvider();
        FakeAiProvider::$nextResponse = AiResponse::text('Hello there!');

        $service = $this->app->make(AiService::class);
        $result = $service->chat('hi there', new \Sharifuddin\LaravelAiBridge\Execution\ExecutionContext());

        $this->assertSame('success', $result['status']);
        $this->assertSame('Hello there!', $result['message']);
    }

    public function test_chat_executes_the_tool_the_ai_selected_when_authorized(): void
    {
        $this->useFakeEmbeddings(['list_users' => [1.0], 'list users' => [1.0]]);
        $this->bindFakeAiProvider();

        $registry = $this->app->make(ToolRegistryInterface::class);
        $tool = new ListUsersTool();
        $registry->register($tool);
        $this->indexTool($tool);

        config(['ai-bridge.retrieval.min_similarity' => 0.0]);

        FakeAiProvider::$nextResponse = AiResponse::toolCall('list_users', ['per_page' => 5]);

        $service = $this->app->make(AiService::class);
        $context = new \Sharifuddin\LaravelAiBridge\Execution\ExecutionContext($this->fakeUser(['view-users']));
        $result = $service->chat('list users', $context);

        $this->assertSame('success', $result['status']);
        $this->assertSame('list_users', $result['action']);
        $this->assertTrue($result['result']['success']);
    }

    public function test_gemini_provider_reads_tool_call_from_any_response_part(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [
                        ['text' => 'Let me check that for you.'],
                        ['functionCall' => [
                            'name' => 'list_users',
                            'args' => ['status' => 'active', 'per_page' => 5],
                        ]],
                    ]],
                ]],
            ], 200),
        ]);

        config(['ai-bridge.gemini.key' => 'test-key']);

        $response = (new GeminiAiProvider())->chat('show active users');

        $this->assertTrue($response->isToolCall());
        $this->assertSame('list_users', $response->toolName);
        $this->assertSame('active', $response->toolArguments['status']);
        $this->assertSame(5, $response->toolArguments['per_page']);
    }

    public function test_chat_returns_permission_error_when_ai_selects_a_tool_the_user_cannot_use(): void
    {
        $this->useFakeEmbeddings(['list_users' => [1.0], 'list users' => [1.0]]);
        $this->bindFakeAiProvider();

        $registry = $this->app->make(ToolRegistryInterface::class);
        $tool = new ListUsersTool();
        $registry->register($tool);
        $this->indexTool($tool);

        config(['ai-bridge.retrieval.min_similarity' => 0.0]);

        FakeAiProvider::$nextResponse = AiResponse::toolCall('list_users', []);

        $service = $this->app->make(AiService::class);
        $context = new \Sharifuddin\LaravelAiBridge\Execution\ExecutionContext($this->fakeUser([]));
        $result = $service->chat('list users', $context);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('permission', strtolower($result['message']));
    }

    public function test_chat_requires_login_before_running_a_protected_tool(): void
    {
        $this->useFakeEmbeddings(['list_users' => [1.0], 'list users' => [1.0]]);
        $this->bindFakeAiProvider();

        $registry = $this->app->make(ToolRegistryInterface::class);
        $tool = new ListUsersTool();
        $registry->register($tool);
        $this->indexTool($tool);

        config(['ai-bridge.retrieval.min_similarity' => 0.0]);

        FakeAiProvider::$nextResponse = AiResponse::toolCall('list_users', []);

        $service = $this->app->make(AiService::class);
        $result = $service->chat('list users', new \Sharifuddin\LaravelAiBridge\Execution\ExecutionContext());

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('login', strtolower($result['message']));
    }

    public function test_only_top_k_declarations_are_sent_to_the_ai_provider_not_the_full_registry(): void
    {
        $this->useFakeEmbeddings([]);
        $this->bindFakeAiProvider();
        FakeAiProvider::$nextResponse = AiResponse::text('ok');

        $registry = $this->app->make(ToolRegistryInterface::class);
        $users = new class extends ListUsersTool {
            public function permission(): ?string
            {
                return null;
            }
        };
        $orders = new GetOrderTool();
        $registry->register($users);
        $registry->register($orders);
        $this->indexTool($users);
        $this->indexTool($orders);

        // Nothing matches semantically/lexically for this unrelated prompt,
        // so the AI provider should receive zero tool declarations rather
        // than the full two-tool registry.
        $service = $this->app->make(AiService::class);
        $service->chat('what is the weather like today', new \Sharifuddin\LaravelAiBridge\Execution\ExecutionContext());

        $this->assertSame([], FakeAiProvider::$lastDeclarations);
    }
}
