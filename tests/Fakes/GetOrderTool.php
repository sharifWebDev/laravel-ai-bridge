<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Fakes;

use Sharifuddin\LaravelAiBridge\Execution\ExecutionContext;
use Sharifuddin\LaravelAiBridge\Tools\AbstractTool;

class GetOrderTool extends AbstractTool
{
    public function name(): string
    {
        return 'get_order';
    }

    public function description(): string
    {
        return 'Find a single order by its invoice number.';
    }

    public function category(): string
    {
        return 'orders';
    }

    public function parametersSchema(): array
    {
        return [
            'invoice_number' => ['type' => 'string', 'required' => true],
        ];
    }

    public function metadata(): array
    {
        return ['entity' => 'order', 'action' => 'get'];
    }

    public function execute(array $arguments, ExecutionContext $context): mixed
    {
        return ['id' => 10023, 'invoice_number' => $arguments['invoice_number']];
    }
}
