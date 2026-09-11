<?php

namespace Sharifuddin\LaravelAiBridge\Exceptions;

class ToolAuthorizationException extends AiBridgeException
{
    public static function forTool(string $name): self
    {
        return new self("You do not have permission for this data. Your role does not allow access to the selected AI action.");
    }

    public static function loginRequired(string $name): self
    {
        return new self("Sorry, this data is only available to authenticated login users. Please login first.");
    }

    public static function missingTenant(string $name): self
    {
        return new self("Sorry, this data is only available to authenticated login users. Please login first.");
    }
}
