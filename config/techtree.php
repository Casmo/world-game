<?php

use App\Enums\BuildingType;

return [

    /*
    |--------------------------------------------------------------------------
    | Default unlocked Buildings
    |--------------------------------------------------------------------------
    |
    | The Building types every Team can place from founding, before any Research
    | (ADR-0003). Everything else must be unlocked one at a time via the tech
    | tree (each Building declares its own prerequisites and research cost).
    |
    */

    'default_unlocked' => [
        BuildingType::Farm->value,
        BuildingType::LumberCamp->value,
        BuildingType::ResearchLab->value,
    ],

];
