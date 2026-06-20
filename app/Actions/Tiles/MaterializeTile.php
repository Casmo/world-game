<?php

namespace App\Actions\Tiles;

use App\Enums\TileResolutionStatus;
use App\Jobs\ResolveTileBiome;
use App\Models\Tile;

/**
 * Lazily materialize a Tile: create its row on first reveal and kick off Biome
 * resolution. Revealing an already-materialized Tile is a no-op (ADR-0008).
 */
class MaterializeTile
{
    public function handle(string $h3Index): Tile
    {
        $tile = Tile::firstOrCreate(
            ['h3_index' => $h3Index],
            ['resolution_status' => TileResolutionStatus::Pending],
        );

        if ($tile->wasRecentlyCreated) {
            ResolveTileBiome::dispatch($tile);
        }

        return $tile;
    }
}
