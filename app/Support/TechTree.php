<?php

namespace App\Support;

use App\Enums\BuildingType;
use App\Enums\TechStatus;
use App\Models\Team;

/**
 * The tech tree: the graph of Building types with their prerequisites and
 * research costs, plus the per-Team status of each node (ADR-0003). The graph
 * itself is defined as data on {@see BuildingType}; this service reads it and
 * projects it through a Team's unlocked set.
 */
class TechTree
{
    /**
     * This Building type's status from the given Team's perspective.
     */
    public function statusFor(Team $team, BuildingType $type): TechStatus
    {
        if ($team->hasUnlocked($type)) {
            return TechStatus::Unlocked;
        }

        foreach ($type->prerequisites() as $prerequisite) {
            if (! $team->hasUnlocked($prerequisite)) {
                return TechStatus::Locked;
            }
        }

        return TechStatus::Available;
    }

    /**
     * The whole tree as view data for a Team: every Building type with its
     * prerequisites, research cost, and current status.
     *
     * @return array<int, array{type: string, label: string, prerequisites: array<int, string>, cost: int, status: string, progress: int}>
     */
    public function forTeam(Team $team): array
    {
        return array_map(fn (BuildingType $type): array => [
            'type' => $type->value,
            'label' => $type->label(),
            'prerequisites' => array_map(fn (BuildingType $p): string => $p->value, $type->prerequisites()),
            'cost' => $type->researchCost(),
            'status' => $this->statusFor($team, $type)->value,
            'progress' => $team->researchProgress($type),
        ], BuildingType::cases());
    }
}
