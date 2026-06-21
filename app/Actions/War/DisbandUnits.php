<?php

namespace App\Actions\War;

use App\Models\Team;

/**
 * Disband Units the Team no longer wants (e.g. ones it can't afford to
 * maintain). Only the Team's own Units are removed.
 *
 * @phpstan-param array<int, int> $unitIds
 */
class DisbandUnits
{
    /**
     * @param  array<int, int>  $unitIds
     */
    public function handle(Team $team, array $unitIds): void
    {
        $team->units()->whereIn('id', $unitIds)->delete();
    }
}
