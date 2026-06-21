<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Unit maintenance
    |--------------------------------------------------------------------------
    |
    | How often standing Units are charged maintenance from the treasury, so a
    | standing army is a continuous drain that must be justified (ADR-0005). The
    | sweep reconciles every elapsed cycle.
    |
    */

    'maintenance_cycle_seconds' => (int) env('WAR_MAINTENANCE_CYCLE_SECONDS', 3600),

    /*
    |--------------------------------------------------------------------------
    | Combat
    |--------------------------------------------------------------------------
    |
    | Combat is deliberately defender-favoured (ADR-0005): the defender's forces
    | are multiplied by the defender advantage, so equal forces favour defence.
    | A Unit type that counters the garrison's composition fights harder. A
    | successful raid loots a margin-scaled fraction of the defender's Resources,
    | capped below the razing tier (Buildings/Units losses arrive in #28).
    |
    */

    'defender_advantage' => (float) env('WAR_DEFENDER_ADVANTAGE', 1.5),
    'counter_bonus' => (float) env('WAR_COUNTER_BONUS', 1.5),
    'raid_loot_per_margin' => (float) env('WAR_RAID_LOOT_PER_MARGIN', 0.25),
    'raid_loot_cap_fraction' => (float) env('WAR_RAID_LOOT_CAP_FRACTION', 0.5),

    /*
    |--------------------------------------------------------------------------
    | Marching
    |--------------------------------------------------------------------------
    |
    | Attacks march for a real-time duration derived from the H3 grid distance
    | between the origin and target Tiles (#6); the return march is the same.
    |
    */

    'march_seconds_per_ring' => (int) env('WAR_MARCH_SECONDS_PER_RING', 600),

    // Money the attacker forfeits when an attack fails (rewarding the defender).
    'attack_failure_penalty' => (int) env('WAR_ATTACK_FAILURE_PENALTY', 50),

];
