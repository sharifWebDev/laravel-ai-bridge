<?php

namespace Sharifuddin\LaravelAiBridge\Exceptions;

class ToolNotFoundException extends AiBridgeException
{
    public static function named(string $name): self
    {
        return new self("No result found for this request. The selected AI method is unavailable or not registered.");
    }
}
