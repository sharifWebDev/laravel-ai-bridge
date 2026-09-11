<?php

namespace Sharifuddin\LaravelAiBridge\Exceptions;

use RuntimeException;

/**
 * Base exception for all AI Bridge domain errors. Catching this type lets
 * host applications handle any package failure without enumerating each
 * concrete exception class.
 */
class AiBridgeException extends RuntimeException
{
}
