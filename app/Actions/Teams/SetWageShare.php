<?php

namespace App\Actions\Teams;

use App\Models\Team;

/**
 * Set a Team's wage share, clamped to the system floor/cap (ADR-0006). The wage
 * can never reach zero (the floor) nor let the Team lose money on labor (the cap).
 */
class SetWageShare
{
    public function handle(Team $team, float $share): void
    {
        $floor = (float) config('money.wage_share_floor');
        $cap = (float) config('money.wage_share_cap');

        $team->forceFill(['wage_share' => max($floor, min($cap, $share))])->save();
    }
}
