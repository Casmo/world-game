<?php

namespace App\Enums;

/**
 * Military Unit types. Units are typed so they can counter one another in combat
 * (counters arrive with the combat-resolution service); players never fight in
 * person (ADR-0003/0005).
 */
enum UnitType: string
{
    case Infantry = 'infantry';
    case Armor = 'armor';
    case Air = 'air';

    /**
     * Base combat strength of one Unit.
     */
    public function strength(): int
    {
        return match ($this) {
            self::Infantry => 10,
            self::Armor => 25,
            self::Air => 20,
        };
    }

    /**
     * Money cost to train one Unit.
     */
    public function trainingCost(): int
    {
        return match ($this) {
            self::Infantry => 50,
            self::Armor => 150,
            self::Air => 120,
        };
    }

    /**
     * How long one Unit takes to train, in seconds.
     */
    public function trainingSeconds(): int
    {
        return match ($this) {
            self::Infantry => 1800,
            self::Armor => 3600,
            self::Air => 2700,
        };
    }

    /**
     * Money charged per maintenance cycle to keep one Unit standing.
     */
    public function maintenancePerCycle(): int
    {
        return match ($this) {
            self::Infantry => 2,
            self::Armor => 5,
            self::Air => 4,
        };
    }

    /**
     * The Unit type this one is strongly effective against (a rock-paper-scissors
     * triangle): Armor beats Infantry, Air beats Armor, Infantry beats Air.
     */
    public function counters(): self
    {
        return match ($this) {
            self::Armor => self::Infantry,
            self::Air => self::Armor,
            self::Infantry => self::Air,
        };
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
