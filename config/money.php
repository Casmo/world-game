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

];
