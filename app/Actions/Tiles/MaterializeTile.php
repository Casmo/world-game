<?php

namespace App\Actions\Tiles;

use App\Enums\TileResolutionStatus;
use App\Jobs\ResolveTileBiome;
use App\Models\Tile;

/**
 * Lazily materialize a Tile: create its row on first reveal and kick off Biome
 * resolution. Resolution is dispatched for any *pending* Tile — covering both
 * freshly-created Tiles and Tiles created without a biome elsewhere (e.g. claimed
 * starting Tiles). The job itself is a no-op once a Tile is resolved, so the geo
 * API is never called for an already-resolved Tile (ADR-0008).
 */
class MaterializeTile
{
    public function handle(string $h3Index): Tile
    {
        $tile = Tile::firstOrCreate(
            ['h3_index' => $h3Index],
            ['resolution_status' => TileResolutionStatus::Pending],
        );

        if ($tile->resolution_status === TileResolutionStatus::Pending) {
            ResolveTileBiome::dispatch($tile);
        }

        return $tile;
    }
}
