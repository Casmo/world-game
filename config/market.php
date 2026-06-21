<?php

use App\Enums\ResourceType;

return [

    /*
    |--------------------------------------------------------------------------
    | NPC world market prices
    |--------------------------------------------------------------------------
    |
    | The price catalogue for the NPC world market (ADR-0006). For each Resource:
    | the "floor" is what the NPC pays a Team when it sells (the money faucet),
    | and the "ceiling" is what a Team pays the NPC when it buys (the money sink).
    | floor < ceiling for every Resource — player-to-player trade lives in the
    | spread between them.
    |
    */

    'prices' => [
        ResourceType::Food->value => ['floor' => 1, 'ceiling' => 3],
        ResourceType::Wood->value => ['floor' => 2, 'ceiling' => 5],
        ResourceType::Stone->value => ['floor' => 3, 'ceiling' => 7],
    ],

];
