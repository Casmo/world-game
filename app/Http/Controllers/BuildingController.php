<?php

namespace App\Http\Controllers;

use App\Actions\Buildings\PlaceBuilding;
use App\Enums\BuildingType;
use App\Enums\TeamRole;
use App\Exceptions\BuildingLockedException;
use App\Models\Building;
use App\Models\Tile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BuildingController extends Controller
{
    /**
     * Place a Building on an empty Plot of an owned Tile (Mayor/Officer only).
     */
    public function store(Request $request, PlaceBuilding $place): RedirectResponse
    {
        $data = $request->validate([
            'tile' => ['required', 'string', 'exists:tiles,h3_index'],
            'type' => ['required', Rule::enum(BuildingType::class)],
            'plot_x' => ['required', 'integer', 'min:0', 'max:'.(Building::PLOT_GRID - 1)],
            'plot_y' => ['required', 'integer', 'min:0', 'max:'.(Building::PLOT_GRID - 1)],
        ]);

        $tile = Tile::findOrFail($data['tile']);
        $team = $tile->team;

        // Placement is a Mayor/Officer act on the owning Team (acting leader =
        // Owner/Admin until elected governance arrives).
        $role = $team ? $request->user()->teamRole($team) : null;
        abort_unless($role !== null && $role->isAtLeast(TeamRole::Admin), 403);

        abort_if(
            Building::where('tile_id', $tile->h3_index)
                ->where('plot_x', $data['plot_x'])
                ->where('plot_y', $data['plot_y'])
                ->exists(),
            422,
            'That plot is already occupied.',
        );

        try {
            $place->handle($tile, BuildingType::from($data['type']), $data['plot_x'], $data['plot_y']);
        } catch (BuildingLockedException $e) {
            abort(422, $e->getMessage());
        }

        return back();
    }
}
