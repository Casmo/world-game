<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an attack is not allowed (e.g. attacking your own Tile, or sending
 * more Units than the Team has available).
 */
class CannotAttackException extends RuntimeException
{
    public function __construct(string $message = 'That attack is not allowed.')
    {
        parent::__construct($message);
    }
}
