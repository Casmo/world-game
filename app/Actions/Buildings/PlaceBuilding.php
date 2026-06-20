<?php

namespace App\Actions\Buildings;

use App\Enums\BuildingState;
use App\Enums\BuildingType;
use App\Models\Building;
use App\Models\Tile;

/**
 * Place a new Building on an empty Plot of a Tile. The Building starts under
 * construction (it must then be built by Work — see StartConstruction).
 *
 * Assumes the caller has already authorized the actor (Mayor/Officer) and that
 * the plot coordinates are valid; at-most-one-Building-per-Plot is guaranteed by
 * the unique index on (tile_id, plot_x, plot_y).
 */
class PlaceBuilding
{
    public function handle(Tile $tile, BuildingType $type, int $plotX, int $plotY): Building
    {
        return Building::create([
            'tile_id' => $tile->h3_index,
            'plot_x' => $plotX,
            'plot_y' => $plotY,
            'type' => $type,
            'state' => BuildingState::UnderConstruction,
            'work_done' => 0,
        ]);
    }
}
