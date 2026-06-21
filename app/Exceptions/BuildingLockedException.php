<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a Team tries to place a Building type it has not yet unlocked via
 * the tech tree (ADR-0003).
 */
class BuildingLockedException extends RuntimeException
{
    public function __construct(string $message = 'This building has not been unlocked yet.')
    {
        parent::__construct($message);
    }
}
