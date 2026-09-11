<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Fakes;

use Sharifuddin\LaravelAiBridge\Contracts\AiProviderInterface;
use Sharifuddin\LaravelAiBridge\DTO\AiResponse;

class FakeAiProvider implements AiProviderInterface
{
    public static ?AiResponse $nextResponse = null;

    /** @var array<int, array<string, mixed>> */
    public static array $lastDeclarations = [];

    public function chat(string $prompt, array $toolDeclarations = [], array $conversationContext = []): AiResponse
    {
        self::$lastDeclarations = $toolDeclarations;

        return self::$nextResponse ?? AiResponse::text('ok');
    }
}
