<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Feature;

use Sharifuddin\LaravelAiBridge\Tests\TestCase;

class RoutesTest extends TestCase
{
    public function test_assistant_chat_page_is_accessible(): void
    {
        $response = $this->get('/ai/assistant');
        $response->assertStatus(200);
        $response->assertSee('AI Bridge Assistant');
    }

    public function test_prompt_api_validates_required_input(): void
    {
        $response = $this->postJson('/api/ai/prompt', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['prompt']);
    }
}
