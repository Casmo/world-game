<?php

namespace App\Enums;

/**
 * The starter catalogue of placeable Buildings. Most are *production* Buildings
 * (they yield a Resource when worked); *service* Buildings like the Bar or the
 * Research Lab yield no sellable goods and exist to serve the Team (ADR-0006).
 */
enum BuildingType: string
{
    case Farm = 'farm';
    case LumberCamp = 'lumber_camp';
    case Quarry = 'quarry';
    case Bar = 'bar';
    case ResearchLab = 'research_lab';
    case Barracks = 'barracks';

    /**
     * Total construction work (in work units) required to finish this Building.
     */
    public function constructionWork(): int
    {
        return match ($this) {
            self::Farm => 30,
            self::LumberCamp => 20,
            self::Quarry => 40,
            self::Bar => 15,
            self::ResearchLab => 20,
            self::Barracks => 35,
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
            self::Bar => 2,
            self::ResearchLab => 3,
            self::Barracks => 2,
        };
    }

    /**
     * The Resource this Building yields when worked, or null if it is not a
     * production Building (e.g. service or military Buildings).
     */
    public function producesResource(): ?ResourceType
    {
        return match ($this) {
            self::Farm => ResourceType::Food,
            self::LumberCamp => ResourceType::Wood,
            self::Quarry => ResourceType::Stone,
            self::Bar, self::ResearchLab, self::Barracks => null,
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
            self::Bar, self::ResearchLab, self::Barracks => 0,
        };
    }

    /**
     * Experience the worker earns in this trade per completed Work shift.
     */
    public function experiencePerShift(): int
    {
        return 10;
    }

    /**
     * Whether working this Building generates Research progress (ADR-0003).
     */
    public function isResearch(): bool
    {
        return $this === self::ResearchLab;
    }

    /**
     * Building types that must be unlocked before this one can be researched
     * (ADR-0003 — progression is per-Building, not per-era).
     *
     * @return array<int, self>
     */
    public function prerequisites(): array
    {
        return match ($this) {
            self::Farm, self::LumberCamp, self::Bar, self::ResearchLab => [],
            self::Quarry => [self::LumberCamp],
            self::Barracks => [self::Quarry],
        };
    }

    /**
     * The Research cost to unlock this Building type.
     */
    public function researchCost(): int
    {
        return match ($this) {
            self::Farm, self::LumberCamp, self::Bar, self::ResearchLab => 0,
            self::Quarry => 100,
            self::Barracks => 150,
        };
    }

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }
}
