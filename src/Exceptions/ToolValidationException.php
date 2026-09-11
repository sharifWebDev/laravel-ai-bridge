<?php

namespace Sharifuddin\LaravelAiBridge\Exceptions;

class ToolValidationException extends AiBridgeException
{
    /** @param array<string, array<int, string>> $errors */
    public function __construct(string $message, protected array $errors = [])
    {
        parent::__construct($message);
    }

    /** @return array<string, array<int, string>> */
    public function errors(): array
    {
        return $this->errors;
    }
}
