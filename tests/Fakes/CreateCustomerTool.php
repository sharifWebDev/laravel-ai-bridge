<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Fakes;

use Sharifuddin\LaravelAiBridge\Execution\ExecutionContext;
use Sharifuddin\LaravelAiBridge\Tools\AbstractTool;

class CreateCustomerTool extends AbstractTool
{
    public function name(): string
    {
        return 'create_customer';
    }

    public function description(): string
    {
        return 'Create a new customer record.';
    }

    public function category(): string
    {
        return 'customers';
    }

    public function requiresTenant(): bool
    {
        return true;
    }

    public function parametersSchema(): array
    {
        return [
            'name' => ['type' => 'string', 'required' => true],
        ];
    }

    public function permission(): ?string
    {
        return 'create-customers';
    }

    public function execute(array $arguments, ExecutionContext $context): mixed
    {
        return ['id' => 55, 'name' => $arguments['name'], 'tenant' => $context->tenant?->toArray()];
    }
}
