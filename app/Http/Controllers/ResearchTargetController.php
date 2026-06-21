<?php

namespace App\Http\Controllers;

use App\Actions\Teams\SetResearchTarget;
use App\Enums\BuildingType;
use App\Enums\TeamRole;
use App\Exceptions\ResearchTargetUnavailableException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ResearchTargetController extends Controller
{
    /**
     * Set the Team's current Research target (Mayor/Officer only).
     */
    public function __invoke(Request $request, SetResearchTarget $setResearchTarget): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($team === null, 404);

        $role = $request->user()->teamRole($team);
        abort_unless($role !== null && $role->isAtLeast(TeamRole::Admin), 403);

        $data = $request->validate([
            'target' => ['required', Rule::enum(BuildingType::class)],
        ]);

        try {
            $setResearchTarget->handle($team, BuildingType::from($data['target']));
        } catch (ResearchTargetUnavailableException $e) {
            abort(422, $e->getMessage());
        }

        return back();
    }
}
