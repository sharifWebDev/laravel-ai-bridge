<?php

namespace Sharifuddin\LaravelAiBridge\Contracts;

interface ToolProviderInterface
{
    /**
     * Get the tool definitions provided by this class.
     *
     * @return array<string, mixed>
     */
    public function getTools(): array;
}
