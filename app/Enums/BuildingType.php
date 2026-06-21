<?php

namespace App\Enums;

/**
 * The starter catalogue of placeable Buildings. (Tech-tree gating arrives in a
 * later slice; for now every type is available.)
 */
enum BuildingType: string
{
    case Farm = 'farm';
    case LumberCamp = 'lumber_camp';
    case Quarry = 'quarry';

    /**
     * Total construction work (in work units) required to finish this Building.
     */
    public function constructionWork(): int
    {
        return match ($this) {
            self::Farm => 30,
            self::LumberCamp => 20,
            self::Quarry => 40,
        };
    }

    /**
     * Maximum number of players who may work this Building at once (construction
     * helpers and, later, producers).
     */
    public function workSlots(): int
    {
        return match ($this) {
            self::Farm => 3,
            self::LumberCamp => 2,
            self::Quarry => 4,
        };
    }

    /**
     * The Resource this Building yields when worked, or null if it is not a
     * production Building (e.g. service or military Buildings, added later).
     */
    public function producesResource(): ?ResourceType
    {
        return match ($this) {
            self::Farm => ResourceType::Food,
            self::LumberCamp => ResourceType::Wood,
            self::Quarry => ResourceType::Stone,
        };
    }

    /**
     * Units of Resource produced by one completed Work shift.
     */
    public function outputPerShift(): int
    {
        return match ($this) {
            self::Farm => 5,
            self::LumberCamp => 8,
            self::Quarry => 6,
        };
    }

    /**
     * Experience the worker earns in this trade per completed Work shift.
     */
    public function experiencePerShift(): int
    {
        return 10;
    }

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }
}
