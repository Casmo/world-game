<?php

namespace App\Actions\War;

use App\Enums\UnitStatus;
use App\Models\Tile;
use App\Models\Unit;

/**
 * Station a Unit on a Tile as a standing defender (ADR-0005).
 */
class GarrisonUnit
{
    public function handle(Unit $unit, Tile $tile): void
    {
        $unit->update([
            'status' => UnitStatus::Garrisoned,
            'tile_id' => $tile->h3_index,
        ]);
    }
}
