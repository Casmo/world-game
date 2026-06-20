<?php

namespace App\Http\Controllers;

use App\Actions\Buildings\StartConstruction;
use App\Exceptions\InsufficientEnergyException;
use App\Exceptions\PlayerBusyException;
use App\Exceptions\WorkSlotsFullException;
use App\Models\Building;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BuildingConstructionController extends Controller
{
    /**
     * Join the construction of a Building (any Team member may help build).
     */
    public function __invoke(Request $request, Building $building, StartConstruction $start): RedirectResponse
    {
        $team = $building->tile->team;
        abort_unless($team !== null && $request->user()->belongsToTeam($team), 403);
        abort_unless($building->isUnderConstruction(), 422, 'This building is already built.');

        try {
            $start->handle($request->user(), $building);
        } catch (PlayerBusyException|WorkSlotsFullException|InsufficientEnergyException $e) {
            abort(422, $e->getMessage());
        }

        return back();
    }
}
