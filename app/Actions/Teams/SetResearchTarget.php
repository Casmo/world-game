<?php

namespace App\Actions\Teams;

use App\Enums\BuildingType;
use App\Enums\TechStatus;
use App\Exceptions\ResearchTargetUnavailableException;
use App\Models\Team;
use App\Support\TechTree;

/**
 * Set a Team's current Research target. Only an "available" Building type — not
 * yet unlocked, with all prerequisites met — may be chosen (ADR-0003).
 */
class SetResearchTarget
{
    public function __construct(private TechTree $techTree) {}

    /**
     * @throws ResearchTargetUnavailableException
     */
    public function handle(Team $team, BuildingType $target): void
    {
        if ($this->techTree->statusFor($team, $target) !== TechStatus::Available) {
            throw new ResearchTargetUnavailableException;
        }

        $team->setResearchTarget($target);
    }
}
