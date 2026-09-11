<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Unit;

use Sharifuddin\LaravelAiBridge\Contracts\ToolRegistryInterface;
use Sharifuddin\LaravelAiBridge\Execution\ExecutionContext;
use Sharifuddin\LaravelAiBridge\Execution\TenantContext;
use Sharifuddin\LaravelAiBridge\Retrieval\ToolRetriever;
use Sharifuddin\LaravelAiBridge\Tests\Fakes\CreateCustomerTool;
use Sharifuddin\LaravelAiBridge\Tests\Fakes\GetOrderTool;
use Sharifuddin\LaravelAiBridge\Tests\Fakes\ListUsersTool;
use Sharifuddin\LaravelAiBridge\Tests\TestCase;

class ToolRetrieverTest extends TestCase
{
    private static int $fakeUserSequence = 0;

    private function fakeUser(array $abilities = []): object
    {
        $id = ++self::$fakeUserSequence;

        return new class ($id, $abilities) {
            public function __construct(private int $id, private array $abilities)
            {
            }

            public function getAuthIdentifier(): int
            {
                return $this->id;
            }

            public function can($ability): bool
            {
                return in_array($ability, $this->abilities, true);
            }
        };
    }

    private function openListUsersTool(): ListUsersTool
    {
        return new class extends ListUsersTool {
            public function permission(): ?string
            {
                return null;
            }
        };
    }

    public function test_ranks_the_semantically_closest_tool_first(): void
    {
        $this->useFakeEmbeddings([
            'list_users' => [1.0, 0.0],
            'get_order' => [0.0, 1.0],
            'show me active users' => [1.0, 0.0],
        ]);

        $registry = $this->app->make(ToolRegistryInterface::class);
        $users = $this->openListUsersTool();
        $orders = new GetOrderTool();
        $registry->register($users);
        $registry->register($orders);
        $this->indexTool($users);
        $this->indexTool($orders);

        config(['ai-bridge.retrieval.min_similarity' => 0.0]);

        $retriever = $this->app->make(ToolRetriever::class);
        $result = $retriever->retrieve('show me active users', new ExecutionContext());

        $this->assertFalse($result->isEmpty());
        $this->assertSame('list_users', $result->candidates[0]->tool->name());
        $this->assertTrue($result->usedVectorSearch);
    }

    public function test_legacy_user_list_queries_are_not_low_confidence_for_active_users(): void
    {
        config([
            'ai-bridge.controllers' => [\App\Http\Controllers\Api\UserController::class],
            'ai-bridge.retrieval.min_similarity' => 0.0,
        ]);

        $this->app->forgetInstance(ToolRegistryInterface::class);
        $registry = $this->app->make(ToolRegistryInterface::class);

        $this->assertTrue($registry->has('user_controller_index') || $registry->has('user_controller_all'));

        $retriever = $this->app->make(ToolRetriever::class);
        $result = $retriever->retrieve('show me all active users', new ExecutionContext());

        $this->assertFalse($result->isEmpty());
        $this->assertFalse($result->isLowConfidence);
        $this->assertGreaterThanOrEqual(0.55, $result->topScore());
    }

    public function test_flags_low_confidence_when_top_score_is_weak(): void
    {
        $this->useFakeEmbeddings([]); // everything embeds to [0,0,0] -> zero semantic similarity

        $registry = $this->app->make(ToolRegistryInterface::class);
        $tool = new GetOrderTool();
        $registry->register($tool);
        $this->indexTool($tool);

        config(['ai-bridge.retrieval.min_similarity' => 0.0]);

        $retriever = $this->app->make(ToolRetriever::class);
        $result = $retriever->retrieve('completely unrelated gibberish about the weather', new ExecutionContext());

        $this->assertTrue($result->isLowConfidence);
    }

    public function test_flags_ambiguity_when_two_top_scores_are_close(): void
    {
        $this->useFakeEmbeddings([
            'tool_a' => [1.0, 0.0],
            'tool_b' => [0.99, 0.01],
            'find something' => [1.0, 0.0],
        ]);

        $registry = $this->app->make(ToolRegistryInterface::class);

        $toolA = new class extends ListUsersTool {
            public function name(): string
            {
                return 'tool_a';
            }

            public function permission(): ?string
            {
                return null;
            }
        };
        $toolB = new class extends ListUsersTool {
            public function name(): string
            {
                return 'tool_b';
            }

            public function permission(): ?string
            {
                return null;
            }
        };

        $registry->register($toolA);
        $registry->register($toolB);
        $this->indexTool($toolA);
        $this->indexTool($toolB);

        config(['ai-bridge.retrieval.min_similarity' => 0.0, 'ai-bridge.retrieval.score_margin' => 0.2]);

        $retriever = $this->app->make(ToolRetriever::class);
        $result = $retriever->retrieve('find something', new ExecutionContext());

        $this->assertTrue($result->isAmbiguous);
    }

    public function test_hides_tools_the_user_lacks_permission_for(): void
    {
        $this->useFakeEmbeddings(['list_users' => [1.0], 'list users' => [1.0]]);

        $registry = $this->app->make(ToolRegistryInterface::class);
        $tool = new ListUsersTool(); // permission: view-users
        $registry->register($tool);
        $this->indexTool($tool);

        config(['ai-bridge.retrieval.min_similarity' => 0.0]);

        $retriever = $this->app->make(ToolRetriever::class);

        $withoutPermission = $retriever->retrieve('list users', new ExecutionContext($this->fakeUser([])));
        $this->assertTrue($withoutPermission->isEmpty());

        $withPermission = $retriever->retrieve('list users', new ExecutionContext($this->fakeUser(['view-users'])));
        $this->assertFalse($withPermission->isEmpty());
    }

    public function test_hides_tenant_scoped_tools_when_no_tenant_context_is_available(): void
    {
        $this->useFakeEmbeddings(['create_customer' => [1.0], 'create customer' => [1.0]]);

        $registry = $this->app->make(ToolRegistryInterface::class);
        $tool = new CreateCustomerTool();
        $registry->register($tool);
        $this->indexTool($tool);

        config(['ai-bridge.retrieval.min_similarity' => 0.0]);

        $retriever = $this->app->make(ToolRetriever::class);
        $user = $this->fakeUser(['create-customers']);

        $noTenant = $retriever->retrieve('create customer', new ExecutionContext($user));
        $this->assertTrue($noTenant->isEmpty());

        $withTenant = $retriever->retrieve('create customer', new ExecutionContext($user, TenantContext::fromArray(['company_id' => 5])));
        $this->assertFalse($withTenant->isEmpty());
    }

    public function test_retrieval_cache_does_not_leak_across_different_users(): void
    {
        $this->useFakeEmbeddings(['list_users' => [1.0], 'list users' => [1.0]]);

        $registry = $this->app->make(ToolRegistryInterface::class);
        $tool = new ListUsersTool();
        $registry->register($tool);
        $this->indexTool($tool);

        config(['ai-bridge.retrieval.min_similarity' => 0.0]);

        $retriever = $this->app->make(ToolRetriever::class);

        $userA = $this->fakeUser(['view-users']);
        $userB = $this->fakeUser([]); // no permission

        $resultA = $retriever->retrieve('list users', new ExecutionContext($userA));
        $resultB = $retriever->retrieve('list users', new ExecutionContext($userB));

        $this->assertFalse($resultA->isEmpty());
        $this->assertTrue($resultB->isEmpty());
    }

    public function test_second_identical_call_is_served_from_cache(): void
    {
        $this->useFakeEmbeddings(['list_users' => [1.0], 'list users' => [1.0]]);

        $registry = $this->app->make(ToolRegistryInterface::class);
        $tool = $this->openListUsersTool();
        $registry->register($tool);
        $this->indexTool($tool);

        config(['ai-bridge.retrieval.min_similarity' => 0.0]);

        $retriever = $this->app->make(ToolRetriever::class);
        $context = new ExecutionContext();

        $retriever->retrieve('list users', $context);
        $provider = $this->app->make(\Sharifuddin\LaravelAiBridge\Contracts\EmbeddingProviderInterface::class);
        $callsAfterFirst = $provider->callCount;
        $retriever->retrieve('list users', $context);

        $this->assertSame($callsAfterFirst, $provider->callCount);
    }
}
