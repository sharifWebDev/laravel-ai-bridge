<?php

namespace Sharifuddin\LaravelAiBridge\Console\Commands;

use Illuminate\Console\Command;
use Sharifuddin\LaravelAiBridge\Contracts\ToolRegistryInterface;

class ListToolsCommand extends Command
{
    protected $signature = 'ai:tools:list';

    protected $description = 'List all currently registered AI tools.';

    public function handle(ToolRegistryInterface $registry): int
    {
        $rows = [];
        foreach ($registry->all() as $tool) {
            $rows[] = [$tool->name(), $tool->category(), $tool->permission() ?? '-', $tool->requiresTenant() ? 'yes' : 'no'];
        }

        $this->table(['Name', 'Category', 'Permission', 'Requires Tenant'], $rows);

        return self::SUCCESS;
    }
}
