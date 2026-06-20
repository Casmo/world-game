<?php

namespace App\Actions\Tiles;

use App\Enums\TileResolutionStatus;
use App\Models\Team;
use App\Models\Tile;
use App\Support\H3;
use RuntimeException;

/**
 * Claim an unowned starting Tile for a newly founded Team (ADR-0002). Search
 * outward from the world's spawn centre and atomically claim the first unowned
 * Tile — Tiles fill contiguously, so Teams spawn near existing ones.
 */
class ClaimStartingTile
{
    public function __construct(private H3 $h3) {}

    public function handle(Team $team): Tile
    {
        $origin = $this->h3->latLngToCell(
            (float) config('h3.default_center.lat'),
            (float) config('h3.default_center.lng'),
            config('h3.resolution'),
        );

        foreach ($this->h3->disk($origin, config('h3.spawn_ring')) as $cell) {
            Tile::firstOrCreate(
                ['h3_index' => $cell],
                ['resolution_status' => TileResolutionStatus::Pending],
            );

            // Atomic claim: only the first Team to flip an unowned Tile wins,
            // so two Teams can never end up owning the same Tile (ADR-0010).
            $claimed = Tile::query()
                ->whereKey($cell)
                ->whereNull('team_id')
                ->update(['team_id' => $team->id]);

            if ($claimed === 1) {
                return Tile::findOrFail($cell);
            }
        }

        throw new RuntimeException('No unowned starting Tile is available near the spawn centre.');
    }
}
