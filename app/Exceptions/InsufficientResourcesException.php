<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a Team tries to sell or spend more of a Resource than it holds.
 */
class InsufficientResourcesException extends RuntimeException
{
    public function __construct(string $message = 'The team does not have enough of that resource.')
    {
        parent::__construct($message);
    }
}
