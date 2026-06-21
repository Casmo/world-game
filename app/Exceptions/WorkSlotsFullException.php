<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a player tries to work a Building that has no free work-slot.
 */
class WorkSlotsFullException extends RuntimeException
{
    public function __construct(string $message = 'This building has no free work slots.')
    {
        parent::__construct($message);
    }
}
