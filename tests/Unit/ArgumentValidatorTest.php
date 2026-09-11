<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Unit;

use Sharifuddin\LaravelAiBridge\Execution\ArgumentValidator;
use Sharifuddin\LaravelAiBridge\Exceptions\ToolValidationException;
use Sharifuddin\LaravelAiBridge\Tests\Fakes\GetOrderTool;
use Sharifuddin\LaravelAiBridge\Tests\Fakes\ListUsersTool;
use Sharifuddin\LaravelAiBridge\Tests\TestCase;

class ArgumentValidatorTest extends TestCase
{
    public function test_applies_declared_defaults_for_missing_optional_params(): void
    {
        $validated = (new ArgumentValidator())->validate(new ListUsersTool(), []);

        $this->assertSame(20, $validated['per_page']);
    }

    public function test_rejects_values_above_declared_maximum(): void
    {
        $this->expectException(ToolValidationException::class);

        (new ArgumentValidator())->validate(new ListUsersTool(), ['per_page' => 999999]);
    }

    public function test_rejects_values_outside_declared_enum(): void
    {
        $this->expectException(ToolValidationException::class);

        (new ArgumentValidator())->validate(new ListUsersTool(), ['status' => 'deleted']);
    }

    public function test_missing_required_parameter_throws(): void
    {
        $this->expectException(ToolValidationException::class);

        (new ArgumentValidator())->validate(new GetOrderTool(), []);
    }

    public function test_strips_undeclared_arguments_the_ai_might_send(): void
    {
        $validated = (new ArgumentValidator())->validate(new GetOrderTool(), [
            'invoice_number' => '10023',
            'raw_sql' => 'DROP TABLE users;',
        ]);

        $this->assertArrayNotHasKey('raw_sql', $validated);
        $this->assertSame('10023', $validated['invoice_number']);
    }

    public function test_valid_arguments_pass_through(): void
    {
        $validated = (new ArgumentValidator())->validate(new ListUsersTool(), [
            'search' => 'jane',
            'status' => 'active',
            'per_page' => 50,
        ]);

        $this->assertSame('jane', $validated['search']);
        $this->assertSame('active', $validated['status']);
        $this->assertSame(50, $validated['per_page']);
    }
}
