<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Unit;

use Sharifuddin\LaravelAiBridge\Retrieval\QueryNormalizer;
use Sharifuddin\LaravelAiBridge\Tests\TestCase;

class QueryNormalizerTest extends TestCase
{
    public function test_lowercases_strips_punctuation_and_tokenizes(): void
    {
        $result = (new QueryNormalizer())->normalize('Show me all Active Users!!');

        $this->assertSame('show me all active users', $result->normalized);
        $this->assertSame(['show', 'all', 'active', 'users'], $result->tokens);
    }

    public function test_preserves_and_tokenizes_non_latin_scripts(): void
    {
        $result = (new QueryNormalizer())->normalize('আমাকে users list দেখাও');

        $this->assertSame(['আমাকে', 'users', 'list', 'দেখাও'], $result->tokens);
    }

    public function test_collapses_whitespace(): void
    {
        $result = (new QueryNormalizer())->normalize("find   the   order \n for invoice 10023");

        $this->assertSame('find the order for invoice 10023', $result->normalized);
    }
}
