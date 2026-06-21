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

];
