<?php

namespace Sharifuddin\LaravelAiBridge\Facades;

use Illuminate\Support\Facades\Facade;
use Sharifuddin\LaravelAiBridge\Contracts\ToolRegistryInterface;

/**
 * Developer-experience facade for registering tools:
 *
 *   AI::tool(ListUsersTool::class);
 *   AI::tool(new GetOrderTool());
 *
 * @method static void tool(\Sharifuddin\LaravelAiBridge\Contracts\ToolInterface|string $tool)
 * @method static void unregister(string $name)
 * @method static bool has(string $name)
 * @method static \Sharifuddin\LaravelAiBridge\Contracts\ToolInterface|null get(string $name)
 * @method static array all()
 * @method static array search(string $needle)
 * @method static string version()
 *
 * @see ToolRegistryInterface
 */
class AI extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ToolRegistryInterface::class;
    }

    /**
     * Alias for tool registration to match the documented "AI::tool(...)"
     * developer experience even though the underlying registry method is
     * register().
     */
    public static function tool(mixed $tool): void
    {
        static::getFacadeRoot()->register($tool);
    }
}
