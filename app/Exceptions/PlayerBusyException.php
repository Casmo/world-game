<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a player tries to start an Activity while another is already active.
 * A player may only perform one Activity at a time.
 */
class PlayerBusyException extends RuntimeException
{
    public function __construct(string $message = 'The player is already performing an activity.')
    {
        parent::__construct($message);
    }
}
