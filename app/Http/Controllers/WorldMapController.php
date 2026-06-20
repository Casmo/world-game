<?php

namespace App\Http\Controllers;

use App\Actions\Tiles\MaterializeTile;
use App\Models\Tile;
use App\Support\H3;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorldMapController extends Controller
{
    /**
     * Show the world map: the Tiles around a centre point, materialized lazily.
     */
    public function __invoke(Request $request, H3 $h3, MaterializeTile $materialize): Response
    {
        $validated = $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $lat = (float) ($validated['lat'] ?? config('h3.default_center.lat'));
        $lng = (float) ($validated['lng'] ?? config('h3.default_center.lng'));

        $center = $h3->latLngToCell($lat, $lng, config('h3.resolution'));

        $cells = $h3->disk($center, config('h3.view_ring'));

        foreach ($cells as $cell) {
            $materialize->handle($cell);
        }

        $ownTeamId = $request->user()?->current_team_id;

        $tiles = Tile::whereIn('h3_index', $cells)
            ->get()
            ->map(fn (Tile $tile) => [
                'h3_index' => $tile->h3_index,
                'biome' => $tile->biome,
                'terrain' => $tile->terrain,
                'base_resources' => $tile->base_resources,
                'status' => $tile->resolution_status->value,
                'center' => $tile->center(),
                'team_id' => $tile->team_id,
                'is_owned' => $tile->isOwned(),
                'is_own_team' => $ownTeamId !== null && $tile->team_id === $ownTeamId,
            ])
            ->values();

        return Inertia::render('world-map', [
            'center' => ['h3_index' => $center, 'lat' => $lat, 'lng' => $lng],
            'tiles' => $tiles,
        ]);
    }
}
