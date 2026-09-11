<?php

namespace Sharifuddin\LaravelAiBridge\Tests\Fakes;

use Sharifuddin\LaravelAiBridge\Execution\ExecutionContext;
use Sharifuddin\LaravelAiBridge\Tools\AbstractTool;

class ListUsersTool extends AbstractTool
{
    public function name(): string
    {
        return 'list_users';
    }

    public function description(): string
    {
        return 'List application users with pagination, search, status filtering and role filtering.';
    }

    public function category(): string
    {
        return 'users';
    }

    public function parametersSchema(): array
    {
        return [
            'search' => ['type' => 'string', 'required' => false, 'description' => 'Search by name or email'],
            'status' => ['type' => 'string', 'required' => false, 'enum' => ['active', 'inactive']],
            'per_page' => ['type' => 'integer', 'required' => false, 'default' => 20, 'min' => 1, 'max' => 100],
        ];
    }

    public function permission(): ?string
    {
        return 'view-users';
    }

    public function metadata(): array
    {
        return ['aliases' => ['users list', 'user list'], 'entity' => 'user', 'action' => 'list'];
    }

    public function execute(array $arguments, ExecutionContext $context): mixed
    {
        return ['data' => [['id' => 1, 'name' => 'Jane']], 'meta' => ['total' => 1]];
    }
}
