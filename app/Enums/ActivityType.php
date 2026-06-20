<?php

namespace App\Enums;

enum ActivityType: string
{
    case Sleep = 'sleep';
    case Construct = 'construct';

    /**
     * How long this activity takes to complete, in seconds. For Construct this
     * is also the minimum time floor for finishing a Building — no number of
     * helpers can complete it faster than one shift.
     */
    public function durationSeconds(): int
    {
        return match ($this) {
            self::Sleep => 8 * 3600,
            self::Construct => 3600,
        };
    }

    /**
     * Get the display label for the activity type.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}
