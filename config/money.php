<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seed Capital
    |--------------------------------------------------------------------------
    |
    | The founding treasury every new Team starts with, so it can pay early
    | Wages before production ramps up (ADR-0006).
    |
    */

    'seed_capital' => (int) env('MONEY_SEED_CAPITAL', 1000),

    /*
    |--------------------------------------------------------------------------
    | Wages
    |--------------------------------------------------------------------------
    |
    | A production Work shift pays the worker a share of the resaleable (NPC
    | floor-price) value of what it produced; the Mayor sets that share within a
    | system floor and cap, so labor is always net-positive for the Team and can
    | never bankrupt it (ADR-0006). Service Buildings (no sellable output) pay a
    | flat floor wage instead.
    |
    */

    'wage_share_default' => (float) env('MONEY_WAGE_SHARE', 0.2),
    'wage_share_floor' => (float) env('MONEY_WAGE_SHARE_FLOOR', 0.1),
    'wage_share_cap' => (float) env('MONEY_WAGE_SHARE_CAP', 0.5),
    'floor_wage' => (int) env('MONEY_FLOOR_WAGE', 2),

];
