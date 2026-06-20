<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Tile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CityController extends Controller
{
    /**
     * Show a Tile's City: its Plot sub-grid and the Buildings on it.
     */
    public function __invoke(Request $request, Tile $tile): Response
    {
        $team = $tile->team;
        abort_unless($team !== null && $request->user()->belongsToTeam($team), 403);

        $buildings = $tile->buildings()
            ->get()
            ->map(fn (Building $building) => [
                'id' => $building->id,
                'type' => $building->type->value,
                'plot_x' => $building->plot_x,
                'plot_y' => $building->plot_y,
                'state' => $building->state->value,
                'work_done' => $building->work_done,
                'work_required' => $building->type->constructionWork(),
                'work_slots' => $building->type->workSlots(),
                'active_builders' => $building->activeBuilderCount(),
            ])
            ->values();

        return Inertia::render('city', [
            'tile' => ['h3_index' => $tile->h3_index, 'biome' => $tile->biome],
            'grid' => Building::PLOT_GRID,
            'buildings' => $buildings,
        ]);
    }
}
