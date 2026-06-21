<?php

namespace App\Http\Controllers;

use App\Actions\War\DisbandUnits;
use App\Actions\War\TrainUnit;
use App\Enums\BuildingType;
use App\Enums\TeamRole;
use App\Enums\UnitType;
use App\Exceptions\InsufficientTreasuryException;
use App\Models\Building;
use App\Models\Team;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    /**
     * Show the Team's forces. Only ever the viewer's own Units — another Team's
     * exact Unit counts are never exposed (Fog of war).
     */
    public function index(Request $request): Response
    {
        $team = $this->currentTeam($request);

        return Inertia::render('units', [
            'units' => $team->units()
                ->get()
                ->map(fn (Unit $unit): array => [
                    'id' => $unit->id,
                    'type' => $unit->type->value,
                    'status' => $unit->status->value,
                    'tile_id' => $unit->tile_id,
                ])
                ->values(),
        ]);
    }

    /**
     * Train Units at a military Building (Mayor/Officer only).
     */
    public function store(Request $request, TrainUnit $trainUnit): RedirectResponse
    {
        $team = $this->authorizedTeam($request);

        $data = $request->validate([
            'building' => ['required', 'integer', 'exists:buildings,id'],
            'type' => ['required', Rule::enum(UnitType::class)],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $barracks = Building::findOrFail($data['building']);
        abort_unless(
            $barracks->type === BuildingType::Barracks
                && $barracks->isBuilt()
                && $barracks->tile->team?->is($team),
            422,
            'Units can only be trained at a built barracks on your own Tile.',
        );

        try {
            $trainUnit->handle($team, $barracks, UnitType::from($data['type']), (int) $data['quantity']);
        } catch (InsufficientTreasuryException $e) {
            abort(422, $e->getMessage());
        }

        return back();
    }

    /**
     * Disband Units (Mayor/Officer only).
     */
    public function destroy(Request $request, DisbandUnits $disbandUnits): RedirectResponse
    {
        $team = $this->authorizedTeam($request);

        $data = $request->validate([
            'units' => ['required', 'array'],
            'units.*' => ['integer'],
        ]);

        $disbandUnits->handle($team, $data['units']);

        return back();
    }

    private function currentTeam(Request $request): Team
    {
        $team = $request->user()->currentTeam;
        abort_if($team === null, 404);

        return $team;
    }

    private function authorizedTeam(Request $request): Team
    {
        $team = $this->currentTeam($request);

        $role = $request->user()->teamRole($team);
        abort_unless($role !== null && $role->isAtLeast(TeamRole::Admin), 403);

        return $team;
    }
}
