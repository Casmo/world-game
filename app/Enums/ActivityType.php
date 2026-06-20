<?php

namespace App\Enums;

enum ActivityType: string
{
    case Sleep = 'sleep';

    /**
     * How long this activity takes to complete, in seconds.
     */
    public function durationSeconds(): int
    {
        return match ($this) {
            self::Sleep => 8 * 3600,
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
