<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a Team's treasury cannot afford a purchase or payment.
 */
class InsufficientTreasuryException extends RuntimeException
{
    public function __construct(string $message = 'The team treasury cannot afford that.')
    {
        parent::__construct($message);
    }
}
