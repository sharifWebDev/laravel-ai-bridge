<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Unit;

use Sharifuddin\LaravelAiBridge\Retrieval\LexicalScorer;
use Sharifuddin\LaravelAiBridge\Retrieval\QueryNormalizer;
use Sharifuddin\LaravelAiBridge\Tests\Fakes\GetOrderTool;
use Sharifuddin\LaravelAiBridge\Tests\Fakes\ListUsersTool;
use Sharifuddin\LaravelAiBridge\Tests\TestCase;

class LexicalScorerTest extends TestCase
{
    public function test_scores_matching_tool_higher_than_unrelated_tool(): void
    {
        $normalizer = new QueryNormalizer();
        $scorer = new LexicalScorer();

        $query = $normalizer->normalize('give me all active users');

        $userScore = $scorer->score($query, new ListUsersTool());
        $orderScore = $scorer->score($query, new GetOrderTool());

        $this->assertGreaterThan($orderScore, $userScore);
        $this->assertGreaterThan(0.0, $userScore);
    }

    public function test_alias_mention_boosts_score(): void
    {
        $normalizer = new QueryNormalizer();
        $scorer = new LexicalScorer();

        $withAlias = $scorer->score($normalizer->normalize('show me the users list'), new ListUsersTool());
        $withoutMatch = $scorer->score($normalizer->normalize('completely unrelated text about weather'), new ListUsersTool());

        $this->assertGreaterThan($withoutMatch, $withAlias);
    }

    public function test_empty_query_scores_zero(): void
    {
        $normalizer = new QueryNormalizer();
        $scorer = new LexicalScorer();

        $this->assertSame(0.0, $scorer->score($normalizer->normalize(''), new ListUsersTool()));
    }
}
