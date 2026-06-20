<?php

namespace App\Jobs;

use App\Enums\TileResolutionStatus;
use App\Models\Tile;
use App\Support\BiomeApi;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Resolves a Tile's Biome from the geographic API and caches it on the Tile.
 * Runs once per Tile: it is dispatched only on first materialization and is a
 * no-op if the Tile is already resolved (ADR-0008).
 */
class ResolveTileBiome implements ShouldQueue
{
    use Queueable;

    public function __construct(public Tile $tile) {}

    public function handle(BiomeApi $api): void
    {
        if ($this->tile->isResolved()) {
            return;
        }

        [$lat, $lng] = $this->tile->center();
        $data = $api->resolve($lat, $lng);

        $this->tile->update([
            'biome' => $data['biome'],
            'terrain' => $data['terrain'],
            'base_resources' => $data['base_resources'],
            'resolution_status' => TileResolutionStatus::Resolved,
        ]);
    }
}
