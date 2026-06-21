<?php

namespace App\Http\Controllers;

use App\Actions\Buildings\StartWork;
use App\Exceptions\InsufficientEnergyException;
use App\Exceptions\PlayerBusyException;
use App\Exceptions\WorkSlotsFullException;
use App\Models\Building;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BuildingWorkController extends Controller
{
    /**
     * Start a production Work shift on a built Building (any Team member may work it).
     */
    public function __invoke(Request $request, Building $building, StartWork $start): RedirectResponse
    {
        $team = $building->tile->team;
        abort_unless($team !== null && $request->user()->belongsToTeam($team), 403);
        abort_unless($building->isBuilt() && $building->isProduction(), 422, 'This building cannot be worked.');

        try {
            $start->handle($request->user(), $building);
        } catch (PlayerBusyException|WorkSlotsFullException|InsufficientEnergyException $e) {
            abort(422, $e->getMessage());
        }

        return back();
    }
}
