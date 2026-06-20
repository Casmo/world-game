<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a player lacks the Energy to perform an activity.
 */
class InsufficientEnergyException extends RuntimeException
{
    public function __construct(string $message = 'Not enough energy to perform this activity.')
    {
        parent::__construct($message);
    }
}
