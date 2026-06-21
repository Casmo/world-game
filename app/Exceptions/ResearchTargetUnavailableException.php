<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a Team tries to research a Building type that is already unlocked
 * or whose prerequisites are not yet met (ADR-0003).
 */
class ResearchTargetUnavailableException extends RuntimeException
{
    public function __construct(string $message = 'That building cannot be researched yet.')
    {
        parent::__construct($message);
    }
}
