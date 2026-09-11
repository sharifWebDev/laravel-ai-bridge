<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Unit;

use Sharifuddin\LaravelAiBridge\Services\SemanticToolSelector;
use Sharifuddin\LaravelAiBridge\Tests\TestCase;

class SemanticToolSelectorTest extends TestCase
{
    public function test_selects_relevant_tools_by_keyword_matching(): void
    {
        $tools = [
            'exportUsers' => [
                'name' => 'exportUsers',
                'description' => 'Export users list into CSV or spreadsheet format',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => ['format' => ['type' => 'STRING']],
                ],
            ],
            'deleteOrder' => [
                'name' => 'deleteOrder',
                'description' => 'Delete an order from database permanently',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => ['id' => ['type' => 'STRING']],
                ],
            ],
        ];

        $selector = new SemanticToolSelector();
        $selected = $selector->selectRelevantTools('Please export user data to csv', $tools);

        $this->assertNotEmpty($selected);
        $declarations = $selected[0]['function_declarations'];
        $this->assertNotEmpty($declarations);
        $this->assertSame('exportUsers', $declarations[0]['name']);
    }

    public function test_formats_declarations_for_gemini_schema(): void
    {
        $tools = [
            'TestController@someAction' => [
                'name' => 'someAction',
                'description' => 'A test action',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [],
                ],
            ],
        ];

        $selector = new SemanticToolSelector();
        $selected = $selector->selectRelevantTools('run test action', $tools);

        $declaration = $selected[0]['function_declarations'][0];
        $this->assertArrayHasKey('name', $declaration);
        $this->assertArrayHasKey('description', $declaration);
        $this->assertArrayHasKey('parameters', $declaration);
        // Ensure internal package properties are omitted from Gemini declarations
        $this->assertArrayNotHasKey('controller', $declaration);
        $this->assertArrayNotHasKey('permission', $declaration);
    }
}
